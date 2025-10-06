import { Injectable } from '@angular/core';
import { ToasterService, TypeToast } from './toastr.service';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { throwError, Subject, Observable } from 'rxjs';
import { map, catchError, tap } from 'rxjs/operators';
import { Router } from '@angular/router';
import { ApiAzurService } from './api-azur.service';

@Injectable({
  providedIn: 'root'
})

export class PermissionsService {

  public userIsConnected: boolean = false;
  public myGrants: any[] = []; // Store the actions for which this user has permission
  public previousHref: string = '';

  userConnectedChange: Subject<boolean> = new Subject<boolean>();
  private userProperties: string[] = [];
  private access_token: string | null = '';

  constructor(private httpClient: HttpClient, private toasterService: ToasterService, private router: Router, private apiAzurService: ApiAzurService) {
    this.userConnectedChange.subscribe((value: boolean) => {
      this.setUserIsConnectedToStorage(value);
    });
    this.userIsConnected = this.getUserIsConnectedFromStorage();
    if (localStorage.getItem('userProperties')) {
      this.userProperties = JSON.parse(localStorage.getItem('userProperties') || '');
    }
    if (localStorage.getItem('myGrants') && localStorage.getItem('myGrants') !== 'null') {
      this.myGrants = Object.values(JSON.parse(localStorage.getItem('myGrants') || ''));
    }
    this.userConnectedChange.next(this.getUserIsConnectedFromStorage());
  }
  getUserIsConnectedFromStorage(): boolean {
    if (localStorage.getItem('userIsConnected'))
      return localStorage.getItem('userIsConnected') === 'true' ? true : false;
    return false;
  }

  setUserIsConnectedToStorage(value: boolean) {
    this.userIsConnected = value;
    localStorage.setItem('userIsConnected', value === true ? 'true' : 'false');
  }

  async initLocalSession() {
    this.apiAzurService.cache = [];
    this.myGrants = [];
    this.userProperties = [];
    this.disconnectUser();
    this.setUserIsConnectedToStorage(false);
    localStorage.clear();
    await this.deleteAllCookies();
  }

  async deleteAllCookies() {
    const cookies = document.cookie.split(";");
    for (let i = 0; i < cookies.length; i++) {
      const cookie = cookies[i];
      const eqPos = cookie.indexOf("=");
      const name = eqPos > -1 ? cookie.substring(0, eqPos) : cookie;
      if (name !== '') {
        document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
      }
    }
    await this.sleep(100);
  }
  sleep(ms: number) {
    return new Promise(resolve => setTimeout(resolve, ms))
  }

  async initBackendSession(redirect: boolean) {
    return await this.apiAzurService.get('logout').then((data: any) => {
      if (data.end_session_endpoint && data.end_session_endpoint.substring(0, 4) === 'http') {
        redirect && window.location.replace(data.end_session_endpoint);
      } else {
        redirect && this.router.navigate(['/']);
      }
    })
  }

  async logout(redirect: boolean = true) {
    await this.initLocalSession();
    await this.initBackendSession(redirect);
  }

  async login() {
    const webEnv =
      window.location.hostname.includes("localhost")
      ? 'localhost'
      : window.location.hostname.includes("-poc.")
      ? 'poc'
      : window.location.hostname.includes("-dev.")
      ? 'dev'
      : 'prod';
    await this.initLocalSession();
    if (environment.auth_method === 'SSO_delegate' && 'SSO_delegate' in environment) {
      /**
       * export const environment = {
       *     auth_method: 'SSO_delegate',
       *     SSO_delegate: 'https://exampleApp.place-cloud-enedis.fr/API/core/SSO_callback/?callback=',
       *     ...
       *   };
       */
      this.openExternalSSO((environment as any).SSO_delegate);
    } else if ('localhost' === webEnv || 'poc' === webEnv) {
      this.openExternalSSO();
    } else if (environment.auth_method === 'SSO_recette' || (environment.auth_method === 'SSO' && webEnv === 'prod')) {
      document.location.replace('/API/core/SSO/');
    } else {
      this.openExternalSSO();
    }
  }

  openExternalSSO(url: string = '') {
    const parts = window.location.href.split('#');
    const href = parts[0];
    let callback = '';
    if ('' !== url) {
      callback = url + href;
    } else if (href.includes('localhost') || href.includes('-poc.')) {
      callback = 'https://appli-stats.place-cloud-enedis.fr/API/core/SSO_callback/?callback=' + href
    } else {
      callback = window.location.origin.replace('-dev.', '.') + '/API/core/SSO_callback/?callback=' + href;
    }
    window.location.replace(callback);
  }

  async previous_login() {
    await this.initLocalSession();
    if ((environment.auth_method === 'external')) {
      this.openExternalSSO();
    } else if ((environment.auth_method === 'SSO')) {
      document.location.replace(environment.api_url + '/core/SSO');
    } else {
      this.router.navigate(['/login']);
    }
  }

  setUser(user: string): void {
    this.userProperties = JSON.parse(window.atob(user));
    localStorage.setItem('userProperties', window.atob(user));
    this.connectUser();
  }
  setGrants(grants: string): void {
    this.myGrants = Object.values(JSON.parse(window.atob(grants)));
    localStorage.setItem('myGrants', window.atob(grants));
    if (this.myGrants.length === 0) {
      this.toasterService.showToast(TypeToast.danger, 'Erreur', 'Vous n\'êtes pas habilité à utiliser cette application.');
      this.disconnectUser();
      this.router.navigate(['/login']);
    } else {
      this.connectUser();
    }
  }

  setAccessToken(assess_token: string): void {
    this.access_token = assess_token;
    localStorage.setItem('token', btoa(assess_token));
  }

  getAccessToken(): string {
    if (null !== localStorage.getItem('token')) {
      this.access_token = localStorage.getItem('token') || '';
      return atob(this.access_token);
    }
    return '';
  }

  getUser(): string[] {
    return this.userProperties;
  }

  toggleUserConnection(): void {
    this.userConnectedChange.next(!this.userIsConnected);
  }

  connectUser(): void {
    this.userConnectedChange.next(true);
  }

  async disconnectUser() {
    await this.userConnectedChange.next(false);
  }

  /**
   * Teste si l'utilisateur possède un droit
   * @param string[]|string requiredGrants `Le(s) droit(s) requis`
   * @return boolean `Le résultat (true/false)`
   */
  hasPermission(requiredGrants: string[] | string): boolean {
    if (Array.isArray(requiredGrants)) {
      for (let i = 0; i < requiredGrants.length; i++) {
        let grant = requiredGrants[i];
        if (this.myGrants && this.myGrants.includes(grant)) {
          return true;
        }
      }
    } else {
      if (this.myGrants && this.myGrants.includes(requiredGrants)) {
        return true;
      }

    }
    return false;
  }

  /**
   * Teste si l'utilisateur possède un droit et demande l'affichage d'une alerte dans le cas où l'utilisateur ne possède pas le(s) droit(s)
   * @param string[]|string requiredGrants `Le(s) droit(s) requis`
   * @return boolean `Le résultat (true/false)`
   */
  hasPermissionWithAlert(requiredGrants: string[] | string): boolean {
    let permission = this.hasPermission(requiredGrants);
    if (!permission) this.toasterService.error(`Accès interdit`, `Vous n'avez pas le droit d'accéder à cette page.`);
    return permission;
  }

  /**
   * Si l'utilisateur n'est pas connecté, on le renvoit vers la page de login.
   * Sinon, on récupère ses droits
   */
  initializeGrants(): Promise<void> {
    return new Promise((resolve, reject) => {
      this.userConnectedChange.next(this.getUserIsConnectedFromStorage());
      // if (this.getUserIsConnectedFromStorage() !== true) {
      //   if (environment.auth_method == 'SSO') {
      //     document.location.replace('/API/core/SSO');
      //   } else {
      //     this.router.navigate(['/login']);
      //   }
      // }
      // Call API to retrieve the list of actions this user is permitted to perform. (Details not provided here.)
      // In this case, the method returns a Promise, but it could have been implemented as an Observable
      this.getMyGrants().pipe(
        tap(() => {
          // Action en cas de succès
        }),
        catchError((e) => {
          return throwError(() => e); // Propagation de l'erreur
        })
      ).subscribe({
        next: () => resolve(),
        error: (e) => reject(e),
      });
    });
  }

  /**
   * Récupération des droits de l'utilisateur
   */
  getMyGrants<K = any>(): Observable<K> {
    return this.httpClient.get<K>(`${environment.api_url}getMyGrants`).pipe(
      catchError((err: any) => {
        // Gérer l'erreur ici si nécessaire
        return throwError(() => new Error(err.message || 'Error fetching grants'));
      })
    );
  }

  obtainMyGrants() {
    this.apiAzurService.get('obtainMyGrants');
  }

}