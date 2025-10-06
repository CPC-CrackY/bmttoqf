import { NgModule               } from '@angular/core';
import { Routes, RouterModule   } from '@angular/router';

import { CoreSSOComponent } from './core-sso.component';

const routes: Routes = [
  {
    path: 'sso',
    component: CoreSSOComponent, data: { title: 'Vérification de vos droits d\'accès', logo: 'assets/images/logo_administration.png' }
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CoreSSORoutingModule { }
