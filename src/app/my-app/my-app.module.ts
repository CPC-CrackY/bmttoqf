import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { MyAppRoutingModule } from './my-app-routing.module';
import { AboutComponent } from './core/about/about.component';
import { SecondaryNavbarsModule } from '../ENEDIS-core/components/navbar/secondary-navbars/secondary-navbars.module';
import { HelpComponent } from './core/help/help.component';
import { CoreGrantersModule } from '../ENEDIS-core/components/core-granters/core-granters.module';
import { CoreRgpdModule } from '../ENEDIS-core/components/core-rgpd/core-rgpd.module';
import { DirectivesModule } from '../ENEDIS-core/directives/directives.module';


@NgModule({
  declarations: [AboutComponent, HelpComponent],
  imports: [
    CommonModule,

    SecondaryNavbarsModule,
    CoreGrantersModule,
    CoreRgpdModule,
    DirectivesModule,
    
    MyAppRoutingModule
  ]
})
export class MyAppModule { }
