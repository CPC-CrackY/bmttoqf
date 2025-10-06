import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-sliders',
  templateUrl: './sliders.component.html',
  styleUrls: ['./sliders.component.scss']
})
export class SlidersComponent implements OnInit {

  public value = 3;
  public class = '';

  constructor() { }

  public ngOnInit() {
    this.controlValue();
  }

  public controlValue() {
    if (this.value > 0 && this.value <= 15) {
      this.class = 'bg-success';
    } else if (this.value > 15 && this.value <= 30) {
      this.class = 'bg-warning';
    } else if (this.value > 30) {
      this.class = 'bg-danger text-white';
    } else {
      this.class = '';
    }
  }

}
