import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

//     npm install ngx-bootstrap --save
import { ProgressbarModule } from 'ngx-bootstrap/progressbar';
import { ButtonsModule } from 'ngx-bootstrap/buttons';
import { BsDropdownModule } from 'ngx-bootstrap/dropdown';
import { CollapseModule } from 'ngx-bootstrap/collapse';
import { ModalModule } from 'ngx-bootstrap/modal';
import { TabsModule } from 'ngx-bootstrap/tabs';
import { TooltipModule } from 'ngx-bootstrap/tooltip';

//     npm install ngx-toastr @angular/animations --save
import { ToastrModule } from 'ngx-toastr';
import { EtapesModule } from '../../../ENEDIS-core/components/etapes/etapes.module';

import { AlertsComponent } from './alerts/alerts.component';
import { ButtonsComponent } from './buttons/buttons.component';
import { CollapseComponent } from './collapse/collapse.component';
import { ChronologiqueComponent } from './chronologique/chronologique.component';
import { FlexgridComponent } from './flexgrid/flexgrid.component';
import { ModalComponent } from './modal/modal.component';
import { ProgressComponent } from './progress/progress.component';
import { SlidersComponent } from './sliders/sliders.component';
import { TabsComponent } from './tabs/tabs.component';
import { ToastrComponent } from './toastr/toastr.component';
import { TooltipsComponent } from './tooltips/tooltips.component';
import { TypographyComponent } from './typography/typography.component';
import { WidgetsComponent } from './widgets.component';

import { WidgetsRoutingModule } from './widgets-routing.module';

import { SecondaryNavbarsModule } from '../../../ENEDIS-core/components/navbar/secondary-navbars/secondary-navbars.module';
import { DirectivesModule } from '../../../ENEDIS-core/directives/directives.module';

@NgModule({
  declarations: [
    WidgetsComponent,
    AlertsComponent,
    ButtonsComponent,
    CollapseComponent,
    ChronologiqueComponent,
    FlexgridComponent,
    ModalComponent,
    ProgressComponent,
    SlidersComponent,
    TabsComponent,
    ToastrComponent,
    TooltipsComponent,
    TypographyComponent,
  ],
  imports: [
    CommonModule,
    WidgetsRoutingModule,
    SecondaryNavbarsModule,

    FormsModule,

    // // from 'ngx-bootstrap/...';
    ProgressbarModule.forRoot(),
    ButtonsModule.forRoot(),
    BsDropdownModule.forRoot(),
    CollapseModule.forRoot(),
    ModalModule.forRoot(),
    TabsModule.forRoot(),
    TooltipModule.forRoot(),

    // Les liste chronologique
    EtapesModule,

    DirectivesModule
  ],
  exports: [
  ]
})
export class WidgetsModule { }
