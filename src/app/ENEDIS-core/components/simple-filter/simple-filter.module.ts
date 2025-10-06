import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SimpleFilterComponent } from './simple-filter.component';
import { NgSelectModule } from '@ng-select/ng-select';
import { FormsModule } from '@angular/forms';
import { CorePipesModule } from '../../pipes/core-pipes.module';

@NgModule({
  declarations: [
    SimpleFilterComponent
  ],
  imports: [
    CommonModule,
    FormsModule,
    NgSelectModule,
    CorePipesModule
  ],
  exports: [
    SimpleFilterComponent,
  ]
})
export class SimpleFilterModule { }
