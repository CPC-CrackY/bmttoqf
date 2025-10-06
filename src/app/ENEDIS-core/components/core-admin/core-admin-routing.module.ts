import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { CoreAdminComponent } from './core-admin.component';
import { CoreAdminRolesListComponent } from './core-admin-roles-list/core-admin-roles-list.component';
import { CoreAdminUsersListComponent } from './core-admin-users-list/core-admin-users-list.component';
import { CanActivateService } from '../../services/can-activate.service';
import { CoreAdminImportsComponent } from './core-admin-imports/core-admin-imports.component';
import { CoreAdminParametersListComponent } from './core-admin-parameters-list/core-admin-parameters-list.component';
import { CoreAdminHealthComponent } from './core-admin-health/core-admin-health.component';
import { CoreAdminDbSyncComponent } from './core-admin-db-sync/core-admin-db-sync.component';

const routes: Routes = [
  {
    path: 'admin',
    data: { bigTitle: 'La zone d\'administration' },
    component: CoreAdminComponent,
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'users' },
      { path: 'roles', component: CoreAdminRolesListComponent, canActivate: [CanActivateService], data: { auth: ['ROLES', 'HABILITATIONS'], title: 'Rôles' } },
      { path: 'users', component: CoreAdminUsersListComponent, canActivate: [CanActivateService], data: { auth: ['HABILITATIONS'], title: 'Utilisateurs' } },
      { path: 'imports', component: CoreAdminImportsComponent, canActivate: [CanActivateService], data: { auth: ['IMPORTS'], title: 'Importations Excel' } },
      { path: 'parameters', component: CoreAdminParametersListComponent, canActivate: [CanActivateService], data: { auth: ['PARAMETRES'], title: 'Paramètres applicatifs' } },
      { path: 'health', component: CoreAdminHealthComponent, canActivate: [CanActivateService], data: { auth: ['PARAMETRES'], title: 'Santé du serveur' } },
      { path: 'dbsync', component: CoreAdminDbSyncComponent, canActivate: [CanActivateService], data: { auth: ['PARAMETRES'], title: 'DB Sync' } },
    ]
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CoreAdminRoutingModule { }
