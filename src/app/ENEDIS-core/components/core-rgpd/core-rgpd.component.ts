import { Component, Input, OnInit } from '@angular/core';
import { ApiAzurService } from '../../services/api-azur.service';

@Component({
  selector: 'app-core-rgpd',
  templateUrl: './core-rgpd.component.html',
  styleUrls: ['./core-rgpd.component.scss']
})
export class CoreRgpdComponent implements OnInit {

  rgpd: string = '';

  @Input() color: 'bleu_enedis' | 'vert_enedis' | 'neutre' | 'vert_fonce' | 'jaune_solaire' | 'rouge' | 'bleu_moyen' | 'turquoise'
                  | 'orange' | 'prune' | 'violet' | 'bleu_fonce' | 'taupe' | 'marron' = 'marron';

  constructor(private apiAzurService: ApiAzurService) {}

  ngOnInit() {
    this.loadRgpd();
    let stylesheet = document.styleSheets[0];
    stylesheet.insertRule(".rgpdContainer { background-color:var(--" + this.color + "_200) !important;}", 0);
    stylesheet.insertRule(".rgpdContainer h3 { color: var(--" + this.color + "_500) !important;}", 0);
    stylesheet.insertRule(".rgpdContainer { color: var(--" + this.color + "_700) !important;}", 0);
  }

  loadRgpd() {
    this.apiAzurService.getOnce('rgpd').then((data: { content: string }) => {
      this.rgpd = data.content;
    });
  }

}
