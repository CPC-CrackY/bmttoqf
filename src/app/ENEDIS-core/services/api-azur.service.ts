import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom, throwError } from 'rxjs';
import { map, catchError } from 'rxjs/operators';

import { environment } from '../../../environments/environment';
import { EncryptionService } from './encryption.service';

@Injectable({
  providedIn: 'root'
})

export class ApiAzurService {

  timeouts: any;
  parametresUtilisateur: any[string];
  // selectionsUtilisateur: any[string] = [];
  selectionsUtilisateur: { [key: string]: any } = {};
  selectionsUtilisateurTmp: { [key: string]: any } = {}; // pour PACO ???
  cache: any[string] = {};
  keyword: string = '';
  doNotEncrypt: boolean = false;

  constructor(
    private httpClient: HttpClient,
    private encryptionService: EncryptionService) {
    this.timeouts = {};
    this.testDNC();
  }

  testDNC() {
    if (sessionStorage.getItem('dnc') !== null || window.location.hostname.includes('localhost')) {
      this.keyword = '?subject=';
      this.doNotEncrypt = true;
    } else {
      this.keyword = '?request=';
      this.doNotEncrypt = false;
    }
  }

  async get<K = any>(params: string, urlApi: string = environment.api_url) {
    this.testDNC();
    let keyword = this.keyword;
    if (urlApi === environment.api_url && !this.doNotEncrypt) {
      params = encodeURIComponent(await this.encryptionService.encrypt({ subject: params }));
    } else {
      keyword = '?subject=';
    }
    try {
      const result = await firstValueFrom(this.httpClient
        .get<K>(`${urlApi}${keyword}${params}`));
      return result;
    } catch (error) {
      throw error;
    }
  }

  async getOnce<K = any>(params: string, urlApi: string = environment.api_url) {
    if (this.cache[params]) return this.cache[params] as K;
    const data = await this.get<K>(params, urlApi);
    this.cache[params] = data;
    return data;
  }

  /**
   * Clean the cache for all the getOnce requests that match
   * the provided list. The cache for requests with params
   * (such as filters) is also cleaned.
   * 
   * Usage : cleanCache('request1', 'request2, ...)
   * will clean the cache for :
   * - 'request1'
   * - 'request2'
   * - 'request2&param1=value1&param2=value2'
   * - ...
   * 
   * cleanCache() nettoie tout le cache !
   */
  cleanCache(...subjects: string[]) {
    if (subjects.length === 0) {
      this.cache = {};
      return;
    }
    Object.keys(this.cache).forEach(getParams => {
      const match = /^([^&]+)(&[^&\n]+)*$/.exec(getParams);
      if (match && match[1] && subjects.includes(match[1])) {
        this.cache[getParams] = undefined;
      }
    });
  }

  async getFiltered<K = any>(params: string, urlApi = environment.api_url): Promise<K> {
    this.testDNC();
    let filters = '';
    if (this.doNotEncrypt) {
      filters = await this.buildFilters(params, urlApi);
    } else {
      filters = encodeURIComponent(await this.buildFilters(params, urlApi));
    }
    return firstValueFrom(this.httpClient.get<K>(`${urlApi}${this.keyword}${filters}`)
      .pipe(
        map(res => res),
        catchError(err => throwError(() => new Error(err)))
      ));
  }

  public async buildFilters(params: string, urlApi: string): Promise<string> {
    this.testDNC();
    // On sépare les données présentes dans les paramètres afin de récupérer les paramètres urls mis à la suite du subject
    const tmp = (`subject=${params}`).split('&'); // Ici on ajoute 'subject=' devant les params afin de bien spécifier la clé du premier élément reconnu par défaut comme étant le subject 
    let object: { [key: string]: string } = {};
    tmp.forEach(str => {
      const splitted = str.split('=');
      const key = splitted[0];
      const value = splitted[1];
      object[key] = value;
    });
    let filters: string = params;
    if (Object.keys(this.selectionsUtilisateur).length !== 0) {
      Object.keys(this.selectionsUtilisateur).forEach(parameter => {
        filters += `&${parameter}=`;
        let values = '';
        const selection = this.selectionsUtilisateur[parameter];

        if (Array.isArray(selection)) {
          values = selection.map((value: any) => encodeURIComponent(value)).join(',');
        } else if (selection instanceof Date) {
          values = encodeURIComponent(selection.toISOString().split('T')[0]);
        } else {
          values = encodeURIComponent(selection);
        }

        filters += values;
        object[parameter] = values;
      });
    }
    if (urlApi === environment.api_url && !this.doNotEncrypt) {
      return await this.encryptionService.encrypt(object);
    } else {
      return await Promise.resolve(filters);
    }
  }

  async httpGetFiltered<K = any>(params: string, urlApi: string = environment.api_url): Promise<K> {
    return this.getFiltered(params, urlApi);
  }

  async getDelayed<K = any>(params: string, delay: number = 200, urlApi: string = environment.api_url): Promise<K> {
    // extraire la clé à partir de la chaîne params
    const key: any = params.indexOf('&') !== -1 ? params.substring(0, params.indexOf('&')) : params;
    if (this.timeouts && this.timeouts[key]) {
      clearTimeout(this.timeouts[key]); // effacer le minuteur précédent si présent
    }
    return new Promise((resolve) => {
      this.timeouts[key] = setTimeout(() => {
        const requestPromise = this.get<K>(params, urlApi); // exécuter la méthode get avec les arguments fournis
        resolve(requestPromise);
      }, delay); // ajouter un nouveau minuteur pour le délai spécifié
    });
  }

  getDelayedAndFiltered<K = any>(params: string, delay: number = 200, urlApi: string = environment.api_url): Promise<K> {
    // extraire la clé à partir de la chaîne params
    const key: any = params.indexOf('&') !== -1 ? params.substring(0, params.indexOf('&')) : params;
    if (this.timeouts && this.timeouts[key]) {
      clearTimeout(this.timeouts[key]); // effacer le minuteur précédent si présent
    }
    return new Promise((resolve) => {
      this.timeouts[key] = setTimeout(() => {
        const requestPromise = this.getFiltered<K>(params, urlApi); // exécuter la méthode get avec les arguments fournis
        resolve(requestPromise);
      }, delay); // ajouter un nouveau minuteur pour le délai spécifié
    });
  }

  async next_post<K = any>(params: object, urlApi: string = `${environment.api_url}`, options = {}): Promise<K> {
    let request: any;
    if (urlApi === environment.api_url && !this.doNotEncrypt) {
      request = { request: this.encryptionService.encrypt(params) };
    } else {
      request = params;
    }
    try {
      const result = await firstValueFrom(this.httpClient
        .post<K>(`${urlApi}`, request, options));
      return result;
    } catch (error) {
      throw error;
    }
  }

  async post<K = any>(params: object, urlApi: string = `${environment.api_url}`, options = {}): Promise<K> {
    this.testDNC();
    let request: any;
    if (urlApi === environment.api_url && !this.doNotEncrypt && !(params instanceof FormData)) {
      request = { request: await this.encryptionService.encrypt(params) };
    } else {
      request = params;
    }
    return new Promise((resolve) => {
      this.httpClient.post<K>(`${urlApi}`, request, options)
        .pipe(
          map(res => res),
          catchError(err => throwError(() => new Error(err)))
        )
        .subscribe((data) => resolve(data))
    })
  }

  async postDelayed<K = any>(formData: any, delay: number = 200, urlApi: string = environment.api_url, options = {}): Promise<K> {
    // extraire la clé à partir de la chaîne params
    const key: any = formData.subject;
    if (this.timeouts && this.timeouts[key]) {
      clearTimeout(this.timeouts[key]); // effacer le minuteur précédent si présent
    }
    return new Promise((resolve) => {
      this.timeouts[key] = setTimeout(() => {
        const requestPromise = this.post<K>(formData, urlApi, options); // exécuter la méthode get avec les arguments fournis
        resolve(requestPromise);
      }, delay); // ajouter un nouveau minuteur pour le délai spécifié
    });
  }

  async uncryptedPost<K = any>(params: object, urlApi: string = `${environment.api_url}`, options = {}): Promise<K> {
    return new Promise((resolve) => {
      this.httpClient.post<K>(`${urlApi}`, params, options)
        .pipe(
          map(res => res),
          catchError(err => throwError(() => new Error(err)))
        )
        .subscribe((data) => resolve(data))
    })
  }

  async put<K = any>(params: object, urlApi: string = `${environment.api_url}`): Promise<K> {
    let request: any;
    if (urlApi === environment.api_url && !this.doNotEncrypt) {
      request = { request: await this.encryptionService.encrypt(params) };
    } else {
      request = params;
    }
    try {
      const result = await firstValueFrom(this.httpClient
        .put<K>(`${urlApi}`, request));
      return result;
    } catch (error) {
      throw error;
    }
  }

  async delete<K = any>(params: string, urlApi: string = `${environment.api_url}`): Promise<K> {
    const request = { request: await this.encryptionService.encrypt({ subject: params }) };
    try {
      const result = await firstValueFrom(this.httpClient
        .post<K>(`${urlApi}`, request));
      return result;
    } catch (error) {
      throw error;
    }
  }

  getWebSocketConf() {
    return this.get(`configWebSocket`);
  }

}
