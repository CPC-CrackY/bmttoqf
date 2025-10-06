import { Component, OnDestroy } from '@angular/core';
import { ApiAzurService } from '../../../services/api-azur.service';
import * as echarts from 'echarts';

@Component({
  selector: 'app-core-admin-health',
  templateUrl: './core-admin-health.component.html',
  styleUrls: ['./core-admin-health.component.scss']
})
export class CoreAdminHealthComponent implements OnDestroy {

  latestMetrics: any;
  public chartData: any = null;
  period: 'today' | '90days' = 'today';
  timer: any;

  constructor(readonly apiAzurService: ApiAzurService) { }

  ngOnInit() {
    this.getLatestMetrics();
    this.getChartData();
    this.setInterval();
  }

  ngOnDestroy() {
    this.clearInterval();
  }

  getLatestMetrics() {
    this.apiAzurService.get('getLastMetrics').then(
      (data: any) => {
        this.latestMetrics = data;
      }
    );
  }

  setInterval() {
    this.clearInterval();
    this.timer = window.setInterval(() => {
      this.getLatestMetrics();
    }, 60000);
  }

  clearInterval() {
    this.timer && window.clearInterval(this.timer);
  }

  getChartData() {
    const api = this.period === 'today' ? 'getTodayMetrics' : 'get90DaysMetrics';
    this.apiAzurService.getOnce(api).then(
      (data: any) => {
        if (data) {
          this.chartData = data;
          this.updateCharts();
        }
      }
    );
  }

  togglePeriod() {
    this.period = this.period === 'today' ? '90days' : 'today';
    this.getChartData();
  }

  updateCharts() {
    this.createChart('cpuChart', 'CPU', 'cpu_percent');
    this.createChart('memChart', 'Mémoire', 'memory_percent');
    this.createChart('diskChart', 'Disque', 'disk_percent');
  }

  createChart(elementId: string, title: string, dataKey: string) {
    const chartDom = document.getElementById(elementId);
    const myChart = echarts.init(chartDom);

    const data = this.chartData.map((item: any) => [item.label, item[dataKey]]);

    const option = {
      title: { text: title },
      tooltip: { trigger: 'axis' },
      xAxis: {
        type: 'category',
        axisLabel: {
          formatter: (value: string) => {
            return this.period === 'today' ? value : value.split('-')[1];
          }
        }
      },
      yAxis: { type: 'value', max: 100 },
      series: [{
        data: data,
        type: 'line',
        smooth: true,
        symbol: 'none',
        lineStyle: { width: 3 },
        emphasis: { focus: 'series' },
        progressive: 500,
        animation: false,
        encode: { x: 0, y: 1 },
        renderItem: (params: any, api: any) => {
          const start = api.coord([api.value(0), api.value(1)]);
          const end = api.coord([api.value(0) + 1, api.value(1)]);
          const height = api.size([0, 1])[1];
          const value = api.value(1);

          let color;
          if (value < 80) {
            color = 'green';
          } else if (value < 95) {
            color = 'yellow';
          } else {
            color = 'red';
          }

          return {
            type: 'line',
            shape: { x1: start[0], y1: start[1], x2: end[0], y2: end[1] },
            style: { stroke: color, lineWidth: 3 }
          };
        }
      }]
    };

    myChart.setOption(option);
  }

  getLineColor(value: number) {
    let color: string;
    if (value < 80) {
      color = '#006400'; // Vert foncé
    } else if (value < 95) {
      color = '#8B8000'; // Jaune foncé
    } else {
      color = '#8B0000'; // Rouge foncé
    }

    return color;
  }

}
