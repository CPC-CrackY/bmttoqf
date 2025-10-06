import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { CoreLoginComponent } from './core-login.component';
import { CoreLogoutComponent } from '../core-logout/core-logout.component';


const routes: Routes = [
  {
    path: 'login',
    component: CoreLoginComponent
  },
  {
    path: 'logout',
    component: CoreLogoutComponent
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CoreLoginRoutingModule { }
