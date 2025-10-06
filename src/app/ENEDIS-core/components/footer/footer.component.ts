import { Router } from '@angular/router';
import { Component, OnInit } from '@angular/core';
import { ApiAzurService } from '../../services/api-azur.service';
import { environment } from '../../../../environments/environment';

export interface GitlabAPIData {
}

@Component({
  selector: 'app-footer',
  templateUrl: './footer.component.html',
  styleUrls: ['./footer.component.scss']
})

export class FooterComponent implements OnInit {
  environment = environment;
  public leftFooter: string = '';
  public rightFooter: string = '';
  public apiEnv: 'localhost' | 'poc' | 'dev' | 'prod' | '' = '';
  public webEnv: 'localhost' | 'poc' | 'dev' | 'prod' | '' = '';

  public frontendVersion: string = 'v. inconnue';
  public backendVersion: string = 'v. inconnue';
  public dockerCreationDate: string = 'inconnue';
  public lastVersionsAvailables: any;
  public title: string = '';

  constructor(private apiAzurService: ApiAzurService, private router: Router) { }

  isNotOnLogin() {
    return this.router.url !== '/login';
  }

  ngOnInit() {
    this.webEnv =
      window.location.hostname.includes("localhost")
      ? 'localhost'
      : window.location.hostname.includes("-poc.")
      ? 'poc'
      : (window.location.hostname.includes("-dev."))
      ? 'dev'
    : 'prod';
    this.apiEnv =
      environment.api_url === 'API/'
      ? this.webEnv
      : (environment.api_url.includes("-poc."))
      ? 'poc'
      : (environment.api_url.includes("-dev."))
      ? 'dev'
    : 'prod';
    this.apiAzurService.getOnce('leftFooter').then((data) => this.leftFooter = data.content);
    this.apiAzurService.getOnce('rightFooter').then((data) => this.rightFooter = data.content);
    this.apiAzurService.getOnce('backendVersion').then((data) => {
      this.backendVersion = data.backendVersion;
      const openIdStatus = data.openIdEnabled === "true" ? 'activé' : 'inactif';
      if (data.dockerCreationDate) this.title = 'SAB = Squelette Applicatif Breton\n'
        + 'Application déployée le ' + data.dockerCreationDate + '.';
      const htmlElement: any = document.getElementById('hiddenValue');
      if (htmlElement) {
        let hiddenValue = htmlElement.getAttribute('data-version');
        if (hiddenValue) {
          this.frontendVersion = hiddenValue.substring(2, hiddenValue.length - 2);
        }
      }
      if (window.location.hostname.includes("localhost") || window.location.hostname.includes("place-cloud")) {
        this.apiAzurService.getOnce('getGitlabApiLastDeployment').then((data) => {
          const date = new Date(data["updated_at"])
          this.title += `\nEnvironnement mis à jour le ${date.toLocaleDateString('fr-FR')} à ${date.toLocaleTimeString('fr-FR')} (${data["status"]})`;
        });
      }
    });
    this.apiAzurService.getOnce('lastVersionsAvailables', 'https://appli-stats.place-cloud-enedis.fr/API/').then((data) => this.lastVersionsAvailables = data);
  }
}
