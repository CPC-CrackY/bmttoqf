import { Injectable } from '@angular/core';
import { HttpEvent, HttpInterceptor, HttpHandler, HttpRequest, HttpResponse, HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { LoaderService } from './loader.service';
import { ToasterService, TypeToast } from './toastr.service';
import { PermissionsService } from './permissions.service';
import { Router, ActivationEnd } from '@angular/router';
import { GlobalConfig } from 'ngx-toastr';
import { HttpCancelService } from './http-cancel.service';
import { environment } from '../../../environments/environment';
import { ApiAzurService } from './api-azur.service';
import Swal from 'sweetalert2';
@Injectable()
export class Interceptor implements HttpInterceptor {

  private timeOut: any;
  private requests: HttpRequest<any>[] = [];
  private applistatsUrl: string = "https://appli-stats.place-cloud-enedis.fr/";

  constructor(
    readonly router: Router,
    readonly permissionsService: PermissionsService,
    readonly loaderService: LoaderService,
    readonly toasterService: ToasterService,
    readonly httpCancelService: HttpCancelService,
    readonly httpClient: HttpClient,
    readonly apiAzurService: ApiAzurService
  ) {
    router.events.subscribe((event: any) => {
      // An event triggered at the end of the activation part of the Resolve phase of routing.
      if (event instanceof ActivationEnd) {
        // Cancel pending calls
        this.httpCancelService.cancelPendingRequests();
      }
    });
  }

  intercept(request: HttpRequest<any>, httpHandler: HttpHandler): Observable<HttpEvent<any>> {

    const token = this.permissionsService.getAccessToken();

    let jsVersion: string = '';
    if (null !== localStorage.getItem('jsVersion')) {
      jsVersion = localStorage.getItem('jsVersion') || '';
    }

    let newHeaders: { [header: string]: string } = {};

    // Vérifier si l'URL de la requête correspond à l'API ou au serveur front-end
    const isApiRequest = request.url.startsWith(environment.api_url);
    const isFrontendRequest = request.url.startsWith(window.location.origin);

    // Définir withCredentials en fonction de la destination de la requête
    const withCredentials = isApiRequest || isFrontendRequest;

    // Ajouter l'en-tête Authorization uniquement si la requête appartient à l'API ou au frontend
    if (withCredentials && token) {
      newHeaders['authorization'] = `Bearer ${token}`;
    }
    if (!(request.body instanceof FormData)) {
      newHeaders['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
    }

    // Clone the request and set the new headers
    const modifiedRequest = request.clone({
      setHeaders: newHeaders,
      withCredentials: withCredentials // true pour l'API et le front-end, false pour les autres serveurs
    });

    this.requests.push(modifiedRequest);
    this.loaderService.isLoading.next(this.requests.length > 0);
    return new Observable<HttpEvent<any>>((observer: any) => {
      const subscription = httpHandler.handle(modifiedRequest)
        .subscribe({
          next: (event: any) => {
            if (event instanceof HttpResponse) {
              this.removeRequest(modifiedRequest);
              observer.next(event);
              // Pour intercepter les headers suivants, il faut que la ligne suivante soit ajoutée au backend :
              const newVersion = event.headers.get('X-Version');
              if (withCredentials && newVersion) {
                if (jsVersion === '' && newVersion !== null && newVersion !== '') {
                  localStorage.setItem('jsVersion', newVersion);
                }
                if (jsVersion != '' && newVersion != '' && newVersion != jsVersion) {
                  console.log('jsVersion=', jsVersion);
                  console.log('newVersion=', newVersion);
                  if (this.timeOut) {
                    clearTimeout(this.timeOut);
                  }
                  this.timeOut = setTimeout(() => {
                    Swal.fire({
                      title: "Une nouvelle version de l\'application est disponible. Veuillez recharger la page dès que possible.",
                      showDenyButton: true,
                      showCancelButton: false,
                      confirmButtonText: "Recharger maintenant",
                      denyButtonText: `Attendre plus tard`,
                    }).then((result: any) => {
                      if (result.isConfirmed) {
                        localStorage.setItem('jsVersion', newVersion);
                        window.location.reload();
                      }
                    });
                  }, 2000);
                }
              }
              // => header('Access-Control-Expose-Headers: X-Destroy-A, X-Destroy-B, X-Destroy-C, X-Destroy-D');
              const user = event.headers.get('X-Destroy-A');
              if (user) { this.permissionsService.setUser(user); }
              const grants = event.headers.get('X-Destroy-B');
              if (grants) { this.permissionsService.setGrants(grants); }
              const url = event.headers.get('X-Destroy-C');
              if (url) { this.router.navigate([url]); }
              const stats = event.headers.get('X-Destroy-D');
              if (stats
                && environment.production
                && !window.location.hostname.includes("-dev.")
                && !window.location.hostname.includes("-poc.")
              ) {
                const params = window.atob(stats).split("&");
                console.log(params);
                const formData = new FormData();
                params.forEach((value, index, array) => {
                  const keyValue = value.split('=');
                  console.log(keyValue[0], keyValue[1]);
                  formData.append(keyValue[0], keyValue[1]);
                });
                this.httpClient.post(
                  this.applistatsUrl + 'API/log-stats.php?a=a',
                  window.atob(stats),
                  { headers: new HttpHeaders().set('Content-Type', 'application/x-www-form-urlencoded') }
                ).subscribe({
                  next: (v: any) => console.log('next=', v),
                  error: (v: any) => console.error('error=', v),
                  complete: () => console.info('complete'),
                });
                this.notifyApplistatsNewLogin();
                this.updateApplistatsApiCallsHistory();
              }
              if (event.body) {
                if (typeof event.body.alertToDisplay !== 'undefined') {
                  let title = typeof event.body.alertToDisplay.title !== 'undefined' ? event.body.alertToDisplay.title : '';
                  let message = typeof event.body.alertToDisplay.message !== 'undefined' ? event.body.alertToDisplay.message : '';
                  let options: GlobalConfig = typeof event.body.alertToDisplay.options !== 'undefined' ? event.body.alertToDisplay.options : this.toasterService.toastrOptions;
                  let type = typeof event.body.alertToDisplay.type !== 'undefined' ? event.body.alertToDisplay.type : 'info';
                  this.toasterService.showToast(
                    type as TypeToast,
                    title,
                    message,
                    options);
                }
              }
            }
          },
          error: (err: any) => {
            console.log(err);
            if (this.timeOut) {
              clearTimeout(this.timeOut);
            }
            this.timeOut = setTimeout(() => {
              let toastConfig: Partial<GlobalConfig> = this.toasterService.toastrOptions;
              toastConfig.maxOpened = 1;
              if (err.status == 400) {
                this.toasterService.showToast(TypeToast.danger, 'ERREUR', `Le serveur n'a pas compris la requête.`, toastConfig);
              } else if (err.status == 401) {
                if (this.router.url !== '/login') {
                  this.permissionsService.initLocalSession();
                  this.toasterService.showToast(TypeToast.danger, 'ERREUR', `Vous devez vous authentifier pour continuer.`, toastConfig);
                  this.router.navigate(['/login']);
                }
              } else if (err.status == 405) {
                localStorage.removeItem('yadsk');
                window.location.reload();
              } else if (err.status == 403) {
                if (modifiedRequest.method === 'POST') {
                  this.toasterService.showToast(TypeToast.danger, 'ERREUR', `Vous n'avez le droit d'effectuer cette action.`, toastConfig);
                } else {
                  this.toasterService.showToast(TypeToast.danger, 'ERREUR', `Vous n'avez le droit d'accéder à cette ressource.`, toastConfig);
                }
              } else {
                this.toasterService.showToast(TypeToast.danger, 'ERREUR', 'Le serveur a retourné une erreur.' + err, toastConfig);
              }
            }, 250);
            this.removeRequest(modifiedRequest);
            observer.error(err);
          },
          complete: () => {
            this.removeRequest(modifiedRequest);
            observer.complete();
          }
        });
      // remove modifiedReq from queue when cancelled
      return () => {
        this.removeRequest(modifiedRequest);
        subscription.unsubscribe();
      };
    });
  }

  private removeRequest(req: HttpRequest<any>): void {
    const i = this.requests.indexOf(req);
    if (i >= 0) {
      this.requests.splice(i, 1);
      // A chaque removeRequest on pousse si il reste des requêtes
      this.loaderService.isLoading.next(this.requests.length > 0);
    }
  }

  /**
   * Envoi de l'url de l'appli et du nni à applistats Place V2 en PROD pour enregistrement de la connexion :
   */
  private notifyApplistatsNewLogin() {

    let base = document.querySelector('base');
    const baseHref = (base?.getAttribute('href') ?? '').slice(0, -1);
    const nni = this.permissionsService.getUser()[0];
    const body = new HttpParams({
      fromObject: {
        subject: "login",
        url: window.location.origin,
        base_href: baseHref,
        ul: window.btoa(nni)
      }
    }).toString();

    this.httpClient.post(
      this.applistatsUrl + 'API/log-monitoring.php',
      body
    ).subscribe({
      next: (v: any) => console.log('next=', v),
      error: (v: any) => console.error('error=', v),
      complete: () => console.info('complete'),
    });
  }

  /**
   * Envoi si nécessaire des requêtes API à applistats Place V2 en PROD pour enregistrement des fonctionnalités API utilisées :
   */
  private updateApplistatsApiCallsHistory() {

    const options = {
      headers: new HttpHeaders({
        'Content-Type': 'application/json'
      })
    };
    const urlParam = window.location.origin;

    // On vérifie auprès d'applistats si il est nécessaire de mettre à jour les données :
    this.httpClient.get(
      this.applistatsUrl + "API/log-monitoring.php?subject=getLastApiCallsUpdateForApp&url=" + encodeURIComponent(urlParam),
      options
    ).subscribe({
      next: ({ dateLastUpdate }: { dateLastUpdate?: string | boolean }) => {
        // Si le résultat est false on ne fait rien
        // Sinon, on récupère la liste des appels API depuis la BDD :
        if (dateLastUpdate !== false) {
          this.apiAzurService.getOnce('getApiCallsFromDate&from=' + dateLastUpdate)
            .then((data: any) => {
              let base = document.querySelector('base');
              const baseHref = (base?.getAttribute('href') ?? '').slice(0, -1);
              const body = new HttpParams({
                fromObject: {
                  subject: "apiCalls",
                  url: urlParam,
                  base_href: baseHref,
                  data: JSON.stringify(data)
                }
              }).toString();

              this.httpClient.post(
                this.applistatsUrl + "API/log-monitoring.php",
                body,
                options
              ).subscribe({
                next: (v: any) => console.log('next=', v),
                error: (v: any) => console.error('error=', v)
              });
            });
        }
      },
      error: (v: any) => console.error('error=', v),
    });
  }
}
