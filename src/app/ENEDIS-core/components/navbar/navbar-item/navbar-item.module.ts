import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

import { NavbarItemComponent } from './navbar-item.component';
import { DirectivesModule } from '../../../directives/directives.module';
import { CorePipesModule } from '../../../pipes/core-pipes.module';

@NgModule({
  declarations: [
    NavbarItemComponent,
  ],
  imports: [
    CommonModule,
    RouterModule,
    DirectivesModule,
    CorePipesModule
  ],
  exports: [
    NavbarItemComponent
  ]
})
export class NavbarItemModule { }
