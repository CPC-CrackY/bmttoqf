import { Component, Input, OnInit } from '@angular/core';
import { ApiAzurService } from '../../services/api-azur.service';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-core-granters',
  templateUrl: './core-granters.component.html',
  styleUrls: ['./core-granters.component.scss']
})
export class CoreGrantersComponent implements OnInit {

  granters: any[] = [];

  @Input() color: 'bleu_enedis' | 'vert_enedis' | 'neutre' | 'vert_fonce' | 'jaune_solaire' | 'rouge' | 'bleu_moyen' | 'turquoise'
    | 'orange' | 'prune' | 'violet' | 'bleu_fonce' | 'taupe' | 'marron' = 'marron';

  constructor(private apiAzurService: ApiAzurService) { }

  ngOnInit(): void {
    let stylesheet = document.styleSheets[0];
    stylesheet.insertRule(".grantersContainer { background-color:var(--" + this.color + "_200) !important;}", 0);
    stylesheet.insertRule(".grantersContainer h3 { color: var(--" + this.color + "_500) !important;}", 0);
    stylesheet.insertRule(".granters .granter { color: var(--" + this.color + "_700) !important;}", 0);
    stylesheet.insertRule(".granters .granter { border-bottom: 1px solid var(--" + this.color + "_300) !important;}", 0);
    this.getGranters();
  }

  getGranters(): void {
    this.apiAzurService.getOnce(`getGranters`).then(data => {
      this.granters = data;
    });
  }

  askGrantsByMail(nni: string, firstname: string) {
    const mailText = 'mailto:' + nni + '?subject=Demande d\'habilitation concernant ' + environment.app_name
      + '&body=Bonjour ' + firstname + ',%0A%0AJe souhaiterais obtenir une habilitation sur ' + environment.app_name + '.%0A%0A( 𝓟𝓻𝓮́𝓬𝓲𝓼𝓮𝔃 𝓵𝓮𝓼 𝓭𝓻𝓸𝓲𝓽𝓼 𝓭𝓸𝓷𝓽 𝓿𝓸𝓾𝓼 𝓪𝓿𝓮𝔃 𝓫𝓮𝓼𝓸𝓲𝓷 ).%0A%0AEn vous remerciant sincèrement.';
    window.location.href = mailText;
  }

}
