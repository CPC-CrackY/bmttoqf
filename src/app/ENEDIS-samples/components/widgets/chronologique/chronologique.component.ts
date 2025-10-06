import { Component, OnInit } from '@angular/core';
import { Etape } from '../../../../ENEDIS-core/components/etapes/etapes.component';

@Component({
  selector: 'app-chronologique',
  templateUrl: './chronologique.component.html',
  styleUrls: ['./chronologique.component.scss']
})
export class ChronologiqueComponent implements OnInit {

  public liste: Etape[] = [
    {
      index: '01',
      contenu: `<h2 class="text-primary">Ma demande</h2>
        Pour m’aider dans mes démarches, je suis les conseils en ligne selon mon profil :
      <ul>
        <li>Particulier</li>
        <li>Entreprise</li>
        <li>Collectivité locale</li>
        <li>Professionnel du batiment</li>
      </ul>
    puis :
      <ul>
        <li>Je complète les informations nécessaires (puissance souhaitée, date à laquelle je souhaite disposer de l’électricité…)</li>
        <li>Je transmets les différents documents (autorisation d’urbanisme, plans…)</li>
        <li>J’envoie ma demande</li>
      </ul>`
    },
    {
      index: '02',
      contenu: `<h2 class="text-primary">Ma proposition</h2>
        <p>Mon dossier est étudié.
        Je reçois ensuite une proposition de raccordement composée d’un devis et d’un descriptif technique des travaux à réaliser.
        Il me suffit de renvoyer le devis signé et de payer l’acompte demandé (par carte bancaire ou chèque).</p>`
    },
    {
      index: '03',
      contenu: `<h2 class="text-primary">Mes travaux</h2>
        <p>Les travaux de raccordement sont programmés.
        Le délai de réalisation des travaux varie selon celui d’obtention des autorisations administratives
        et de la nécessité éventuelle d’une extension de réseau.
        Il est nécessaire que mes travaux soient terminés avant toute intervention</p>`
    },
    {
      index: '04',
      contenu: `<h2 class="text-primary">La mise en service de mon installation électrique</h2>
        <p>Une fois les travaux effectués par nos techniciens :<p>
        <ul>
          <li>J’obtiens une attestation de conformité de mon installation appelé
          « <a href="http://www.consuel.com/" target="_blank">Consuel</a> »
          que transmettrai au technicien le jour de la mise en service</li>
          <li>Je règle le solde du devis</li>
          <li>Je choisis un <a href="http://www.energie-info.fr/" target="_blank">fournisseur et lui demande la mise en service</a></li>
          <li>Mon  fournisseur transmet à Enedis ma demande</li>
          <li>Sous 10 jours maximum, la mise en service peut avoir lieu</li>
        </ul>`
    },
  ];

  constructor() { }

  ngOnInit() {
  }

}
