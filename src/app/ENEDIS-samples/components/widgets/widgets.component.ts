import { Component, OnInit } from '@angular/core';
import { Link } from '../../../ENEDIS-core/components/navbar/link';

@Component({
  selector: 'app-widgets',
  templateUrl: './widgets.component.html',
  styleUrls: ['./widgets.component.scss']
})
export class WidgetsComponent implements OnInit {

  links: Link[] = [
    {
      label: 'Alertes',
      url: '/widgets/alerts'
    },
    {
      label: 'Boutons',
      url: '/widgets/buttons'
    },
    {
      label: 'Listes chronologiques',
      url: '/widgets/chronologie'
    },
    {
      label: 'Collapse',
      url: '/widgets/collapse'
    },
    {
      label: 'Grille',
      url: '/widgets/grille'
    },
    {
      label: 'Modals',
      url: '/widgets/modal'
    },
    {
      label: 'Progress',
      url: '/widgets/progress'
    },
    {
      label: 'Sliders',
      url: '/widgets/sliders'
    },
    {
      label: 'Tabs',
      url: '/widgets/tabs'
    },
    {
      label: 'Toastr',
      url: '/widgets/toastr'
    },
    {
      label: 'Tooltips',
      url: '/widgets/tooltips'
    },
    {
      label: 'Typographie',
      url: '/widgets/typography'
    },
    {
      label: 'Accès interdit',
      url: '/widgets/prohibiten',
      requiredPermissionToDisplaySilently: ['HABILITATIONS']
    }
  ];

  constructor() { }

  ngOnInit() {
  }

}
