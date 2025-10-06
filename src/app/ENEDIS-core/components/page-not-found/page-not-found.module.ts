import { NgModule } from '@angular/core';

import { PageNotFoundComponent } from './page-not-found.component';
import { SecondaryNavbarsModule } from '../navbar/secondary-navbars/secondary-navbars.module';

@NgModule({
  declarations: [
    PageNotFoundComponent
  ],
  imports: [
    SecondaryNavbarsModule,
  ],
  exports: []
})
export class PageNotFoundModule { }
