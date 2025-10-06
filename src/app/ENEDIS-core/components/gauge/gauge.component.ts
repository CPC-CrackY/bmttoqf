import { Component, Input, OnChanges, SimpleChanges, OnInit } from '@angular/core';
import { EChartsOption } from 'echarts';

@Component({
  selector: 'app-gauge',
  templateUrl: './gauge.component.html',
  styleUrls: ['./gauge.component.scss']
})

export class GaugeComponent implements OnInit, OnChanges {

  @Input() value: number = 0;
  @Input() formatter: string = '{value} %';
  @Input() color: any = [
    [0.7999, '#41A57D'],
    [0.95, '#FFC328'],
    [1, '#E10028']
  ];
  @Input() width: number = 200;
  @Input() height: number = 200;
  style: any;
  chartOption!: EChartsOption;

  ngOnInit(): void {
    this.style = { width: this.width + 'px', height: this.height + 'px' };
    this.updateChartOption();
  }

  ngOnChanges(changes: SimpleChanges): void {
    this.updateChartOption();
  }

  updateChartOption(): void {
    this.chartOption = {
      series: [
        {
          type: 'gauge',
          progress: {
            show: false,
            width: this.width / 25
          },
          axisLine: {
            lineStyle: {
              width: this.width / 25,
              color: this.color
            }
          },
          pointer: {
            itemStyle: {
              color: 'auto'
            }
          },
          axisTick: {
            show: false
          },
          splitLine: {
            length: this.width / 25,
            lineStyle: {
              width: this.width / 200,
              color: '#999'
            }
          },
          axisLabel: {
            distance: this.width / 15,
            color: 'inherit',
            fontSize: this.width / 25
          },
          title: {
            show: false
          },
          detail: {
            valueAnimation: true,
            fontSize: this.width / 10,
            offsetCenter: [0, '70%'],
            formatter: '{value} %',
            color: 'inherit',
          },
          data: [
            {
              value: this.value
            }
          ]
        }
      ]
    };
  }

}
