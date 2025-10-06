import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

import { SecondaryNavbarsComponent } from './secondary-navbars.component';
import { NavbarItemModule } from '../navbar-item/navbar-item.module';

@NgModule({
  declarations: [
    SecondaryNavbarsComponent
  ],
  imports: [
    CommonModule,
    RouterModule,
    NavbarItemModule
  ],
  exports: [
    SecondaryNavbarsComponent
  ]
})
export class SecondaryNavbarsModule { }
