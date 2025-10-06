import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { HelpComponent } from './core/help/help.component';
import { AboutComponent } from './core/about/about.component';


const routes: Routes = [
  { path: 'aide',
    data: {bigTitle: 'Aide'},
    component: HelpComponent,
  },
  { path: 'about',
    data: { bigTitle: 'A propos' },
    component: AboutComponent,
  },
  {path: '', redirectTo: 'about', pathMatch: 'full'}
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class MyAppRoutingModule { }
