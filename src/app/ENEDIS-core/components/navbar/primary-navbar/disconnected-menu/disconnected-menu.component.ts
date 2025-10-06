import { Component, Input, OnInit } from '@angular/core';
import { Link } from '../../link';

@Component({
  selector: 'app-disconnected-menu',
  templateUrl: './disconnected-menu.component.html',
  styleUrls: ['./disconnected-menu.component.scss']
})
export class DisconnectedMenuComponent implements OnInit {

  email = '';

  private varParent: any;

  @Input() set parent(valeur: Link) {
    if (valeur.url) {
      valeur.url = valeur.url.substring(0, 1) === '/' ? valeur.url.slice(0, 1) : valeur.url;
    }
    this.varParent = valeur;
  }

  get parent(): Link {
    return this.varParent;
  }

  constructor() { }

  ngOnInit() {

  }

}