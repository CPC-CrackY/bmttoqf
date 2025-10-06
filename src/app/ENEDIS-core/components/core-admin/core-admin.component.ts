import { Component, OnInit } from '@angular/core';
import { Link } from '../navbar/link';

@Component({
  selector: 'app-core-admin',
  templateUrl: './core-admin.component.html',
  styleUrls: ['./core-admin.component.scss']
})
export class CoreAdminComponent implements OnInit {

  links: Link[] = [
    {
      label: '👨‍👩‍👦‍👦   Utilisateurs',
      url: '/admin/users',
      requiredPermissionToDisplaySilently: ["HABILITATIONS"]
    },
    {
      label: '🔐   Rôles',
      url: '/admin/roles',
      requiredPermissionToDisplaySilently: ["ROLES", "HABILITATIONS"]
    },
    {
      label: '💾   Importations',
      url: '/admin/imports',
      requiredPermissionToDisplaySilently: ["IMPORTS"]
    },
    {
      label: '⚙️   Paramètres',
      url: '/admin/parameters',
      requiredPermissionToDisplaySilently: ["PARAMETRES"]
    },
    {
      label: '❤️   Santé',
      url: '/admin/health',
      requiredPermissionToDisplaySilently: ["PARAMETRES"]
    },
    {
      label: '💾   DB sync',
      url: '/admin/dbsync',
      requiredPermissionToDisplaySilently: ["PARAMETRES"]
    },
  ];

  constructor() { }

  ngOnInit(): void {
  }

}
