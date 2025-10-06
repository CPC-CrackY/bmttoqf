import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-buttons',
  templateUrl: './buttons.component.html',
  styleUrls: ['./buttons.component.scss']
})
export class ButtonsComponent implements OnInit {

  checkModel: any = { left: false, middle: true, right: false };
  singleModel = '1';
  radioModel = 'Middle';
  uncheckableRadioModel = 'Middle';

  constructor() { }

  ngOnInit() {
  }

}
