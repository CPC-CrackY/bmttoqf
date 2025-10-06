import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GaugeComponent } from './gauge.component';
import { NgxEchartsModule } from 'ngx-echarts';


@NgModule({
  declarations: [
    GaugeComponent
  ],
  imports: [
    CommonModule,

    NgxEchartsModule.forRoot({
      /**
       * This will import all modules from echarts.
       * If you only need custom modules,
       * please refer to [Custom Build] section.
       */
      echarts: () => import('echarts'), // or import('./path-to-my-custom-echarts')
    }),
  ],
  exports: [
    GaugeComponent,
  ]
})
export class GaugeModule { }
