import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CoreSSOComponent } from './core-sso.component';
import { CoreSSORoutingModule } from './core-sso-routing.module';
import { SecondaryNavbarsModule } from '../navbar/secondary-navbars/secondary-navbars.module';



@NgModule({
  declarations: [CoreSSOComponent],
  imports: [
    CommonModule,
    SecondaryNavbarsModule,
    CoreSSORoutingModule
  ]
})
export class CoreSSOModule { }
