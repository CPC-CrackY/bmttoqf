import { Component, OnInit, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { PrimaryNavbarElements } from './primary-navbar-elements';
import { PermissionsService } from '../../../services/permissions.service';
import { environment } from '../../../../../environments/environment';
import { RouteChangeService } from '../../../services/route-change.service';


@Component({
  selector: 'app-primary-navbar',
  templateUrl: './primary-navbar.component.html',
  styleUrls: ['./primary-navbar.component.scss']
})

export class PrimaryNavbarComponent implements OnInit {

  primaryNavbar: PrimaryNavbarElements = {};

  public bigTitle: string = '';
  public title = this.routeChangeService.title;
  public logo: string = '';
  public apiEnv = this.routeChangeService.apiEnv;
  public webEnv = this.routeChangeService.webEnv;

  @Input() links?: PrimaryNavbarElements;

  constructor(
    private httpClient: HttpClient,
    private permissionsService: PermissionsService,
    private routeChangeService: RouteChangeService
  ) {
    if (
      environment.production
      && !window.location.hostname.includes("-dev.enedis.fr")
      && !window.location.hostname.includes("-dev.place-cloud-enedis.fr")
      && !window.location.hostname.includes("-poc.enedis.fr")
      && !window.location.hostname.includes("-poc.place-cloud-enedis.fr")
    ) {
      // En production, la console ne doit pas s'afficher
      if (window && !localStorage.getItem('debug')) {
        window.console.log = window.console.warn = window.console.error = window.console.info = function () { };
      }
    }
    this.httpClient.get('assets/data/menu.json').subscribe((data: any) => this.primaryNavbar = data);
  }

  isUserConnected(): boolean {
    return this.permissionsService.userIsConnected;
  }

  isLoginPage(): boolean {
    return window.location.href.indexOf('login') !== -1;
  }

  isSSO(): boolean {
    return ((environment.auth_method === 'SSO') && !((location.hostname === "localhost" || location.hostname === "127.0.0.1")));
  }

  ngOnInit(): void {
    this.routeChangeService.logNewRoute();
    this.routeChangeService.bigTitle.subscribe((value: string) => {
      this.bigTitle = value;
    })
    this.routeChangeService.logo.subscribe((value: string) => {
      this.logo = value;
    })
  }

  toggleNavbar() {
    const navbarMenu = document.getElementById('navbar-toggler-id');
    navbarMenu?.classList.toggle('collapse');
  }

}
