import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { EnedisCoreRoutingModule } from './enedis-core-routing.module';

import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { FormsModule } from '@angular/forms';

// npm install --save @swimlane/ngx-datatable
import { NgxDatatableModule } from '@swimlane/ngx-datatable';

// npm install --save ngx-bootstrap
import { ProgressbarModule } from 'ngx-bootstrap/progressbar';
import { ButtonsModule } from 'ngx-bootstrap/buttons';
import { BsDropdownModule } from 'ngx-bootstrap/dropdown';
import { CollapseModule } from 'ngx-bootstrap/collapse';
import { ModalModule } from 'ngx-bootstrap/modal';
import { TabsModule } from 'ngx-bootstrap/tabs';
import { TooltipModule } from 'ngx-bootstrap/tooltip';

// npm install --save ngx-toastr @angular/animations
import { ToastrModule } from 'ngx-toastr';

// npm install @ng-select/ng-select@2.20.5 --save
import { NgSelectModule } from '@ng-select/ng-select';


import { FooterComponent } from './components/footer/footer.component';
import { SecondaryNavbarsModule } from './components/navbar/secondary-navbars/secondary-navbars.module';
import { PrimaryNavbarModule } from './components/navbar/primary-navbar/primary-navbar.module';


import { EtapesModule } from './components/etapes/etapes.module';
import { Interceptor } from './services/interceptor';
import { SimpleFilterModule } from './components/simple-filter/simple-filter.module';
import { CoreLoginModule } from './components/core-login/core-login.module';
import { CoreSSOModule } from './components/core-sso/core-sso.module';
import { CoreLoginComponent } from './components/core-login/core-login.component';
import { CoreLogoutComponent } from './components/core-logout/core-logout.component';
import { PageNotFoundModule } from './components/page-not-found/page-not-found.module';
import { DirectivesModule } from './directives/directives.module';
import { CoreAdminModule } from './components/core-admin/core-admin.module';
import { CorePipesModule } from './pipes/core-pipes.module';
// import { SseService } from './services/sse.service';

@NgModule({
  declarations: [
    // Le pied-de-page :
    FooterComponent,
    CoreLoginComponent,
    CoreLogoutComponent,
  ],
  imports: [
    CommonModule,
    BrowserAnimationsModule,
    HttpClientModule,

    // l'ensemble des navbars
    PrimaryNavbarModule,
    SecondaryNavbarsModule,

    // Les listes chronologique
    EtapesModule,
    SimpleFilterModule,

    FormsModule,

    // from '@swimlane/ngx-datatable';
    NgxDatatableModule,

    // from 'ng2-charts';
    // ChartsModule,

    // from '@ng-select'
    NgSelectModule,

    // from 'ngx-bootstrap/...';
    ProgressbarModule.forRoot(),
    ButtonsModule.forRoot(),
    BsDropdownModule.forRoot(),
    CollapseModule.forRoot(),
    ModalModule.forRoot(),
    TabsModule.forRoot(),
    TooltipModule.forRoot(),

    // from 'ngx-toastr';
    ToastrModule.forRoot(),

    PageNotFoundModule,

    CoreLoginModule,

    CoreSSOModule,

    CoreAdminModule,

    EnedisCoreRoutingModule,

    DirectivesModule,
    CorePipesModule
  ],
  exports: [
    // l'ensemble des navbars
    PrimaryNavbarModule,
    SecondaryNavbarsModule,

    // Les listes chronologique
    EtapesModule,
    SimpleFilterModule,

    // Le pied-de-page :
    FooterComponent,

    FormsModule,

    // from '@swimlane/ngx-datatable';
    NgxDatatableModule,

    // from 'ng2-charts';
    // ChartsModule,

    // from '@ng-select'
    NgSelectModule,

    // from 'ngx-bootstrap/...';
    ProgressbarModule,
    ButtonsModule,
    BsDropdownModule,
    CollapseModule,
    ModalModule,
    TabsModule,
    TooltipModule,

    // from 'ngx-toastr';
    ToastrModule,

    PageNotFoundModule,

    EnedisCoreRoutingModule,
  ],
  providers: [
    // SseService,
    Interceptor,
    { provide: HTTP_INTERCEPTORS, useClass: Interceptor, multi: true }
  ]
})
export class EnedisCoreModule { }
