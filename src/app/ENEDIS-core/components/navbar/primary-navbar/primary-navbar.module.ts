import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PrimaryNavbarComponent } from './primary-navbar.component';
import { RouterModule } from '@angular/router';
import { ConnectedMenuComponent } from './connected-menu/connected-menu.component';
import { DisconnectedMenuComponent } from './disconnected-menu/disconnected-menu.component';
import { DirectivesModule } from '../../../directives/directives.module';
import { NavbarItemModule } from '../navbar-item/navbar-item.module';

@NgModule({
  declarations: [PrimaryNavbarComponent, ConnectedMenuComponent, DisconnectedMenuComponent],
  imports: [
    CommonModule,
    RouterModule,
    NavbarItemModule,
    DirectivesModule
  ],
  exports: [
    PrimaryNavbarComponent
  ],
})
export class PrimaryNavbarModule { }
