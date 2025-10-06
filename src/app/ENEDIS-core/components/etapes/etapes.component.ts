import { Component, OnInit, Input } from '@angular/core';

export interface Etape {
  // Index de l'étape
  index?: string;
  // Description de l'étape
  contenu?: string;
  // Classe appliquée à la fiche de l'étape
  class?: string;
}

export interface ListeEtapes {
  // Tableau des étapes de la chronologie
  etapes?: Etape[];
  /**
   * Permet de changer la position de la ligne ou colonne numéroté avec les paramètres suivants :
   * - top (pour l'horizontale)
   * - right (pour la verticale)
   */
  position?: string;
  // Classe du conteneur de la chronologie
  class?: string;
}

@Component({
  selector: 'app-etapes',
  templateUrl: './etapes.component.html',
  styleUrls: ['./etapes.component.scss']
})
export class EtapesComponent implements OnInit {

  // @Input() liste: Etape[];
  @Input() liste?: any[];

  constructor() { }

  ngOnInit() {
  }

}
