import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Title } from '@angular/platform-browser';
import { ActivationStart, Router } from '@angular/router';
import { PermissionsService } from './permissions.service';
// import { SseService } from './sse.service';
import { environment } from '../../../environments/environment';
import { Subject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class RouteChangeService {

  constructor(private httpClient: HttpClient,
    private router: Router,
    private titleService: Title,
    private permissionsService: PermissionsService,
    // private sseService: SseService
  ) { }


  private timeout: any;
  private applistatsUrl: string = "https://appli-stats.place-cloud-enedis.fr/";

  public bigTitle: Subject<string> = new Subject<string>();
  public title = '';
  public logo: Subject<string> = new Subject<string>();
  routeData: any;
  public apiEnv: 'localhost' | 'poc' | 'dev' | 'prod' | '' = '';
  public webEnv: 'localhost' | 'poc' | 'dev' | 'prod' | '' = '';

  logNewRoute() {

    this.webEnv =
      window.location.hostname.includes("localhost")
      ? 'localhost'
      : window.location.hostname.includes("-poc.")
      ? 'poc'
      : window.location.hostname.includes("-dev.")
      ? 'dev'
      : 'prod';
    this.apiEnv =
      environment.api_url === 'API/'
      ? this.webEnv
      : environment.api_url.includes("-poc.")
      ? 'poc'
      : environment.api_url.includes("-dev.")
      ? 'dev'
      : 'prod';
    this.logo.next(environment.logo);
    this.routeData = this.router.events.subscribe((data: any) => {
      this.routeTitle(data);
      // this.sseService.subscribeToCoreSSE(environment.api_url + 'core/core_sse.php', 'core', function (event) {
      //     console.log(event);
      // });
      if (this.timeout) window.clearTimeout(this.timeout);
      this.timeout = window.setTimeout(() => {
        if (
          this.permissionsService.userIsConnected
          && this.permissionsService.previousHref != window.location.href
          && environment.production
          && !window.location.hostname.includes("-dev.")
          && !window.location.hostname.includes("-poc.")
        ) {
          this.permissionsService.previousHref = window.location.href;
          this.notifyApplistatsNewRoute();
        }
      }, 500);
    });
  }

  routeTitle(data: any) {
    if (data instanceof ActivationStart) {
      let title = data.snapshot.data['title'];
      let bigTitle = data.snapshot.data['bigTitle'];
      if (bigTitle && title) {
        this.bigTitle.next(bigTitle);
        this.title = title;
        this.titleService.setTitle(`${environment.app_name} - ${bigTitle} - ${this.title}`);
      } else if (bigTitle) {
        this.bigTitle.next(bigTitle);
        this.titleService.setTitle(`${environment.app_name} - ${bigTitle}`);
      } else if (title) {
        this.title = title;
        this.titleService.setTitle(`${environment.app_name} - ${this.title}`);
      }
      this.logo.next((data.snapshot && data.snapshot.data && data.snapshot.data['logo']) || environment.logo);
    }
  }

  /**
   * Envoi de l'url de l'appli à applistats Place V2 en PROD pour enregistrement du chargment de la page :
   */
  private notifyApplistatsNewRoute() {
    let base = document.querySelector('base');
    const baseHref = (base?.getAttribute('href') ?? '').slice(0, -1);
    const body = new HttpParams({
      fromObject: {
        subject: "routes",
        url: window.location.href.split('?')[0],
        base_href: baseHref
      }
    }).toString();

    this.httpClient.post(
      this.applistatsUrl + 'API/log-monitoring.php',
      body
    ).subscribe({
      next: (v) => console.log('next=', v),
      error: (v) => console.error('error=', v),
      complete: () => console.info('complete'),
    });
  }
}