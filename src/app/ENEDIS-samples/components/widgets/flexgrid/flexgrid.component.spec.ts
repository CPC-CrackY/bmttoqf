import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { FlexgridComponent } from './flexgrid.component';

describe('FlexgridComponent', () => {
  let component: FlexgridComponent;
  let fixture: ComponentFixture<FlexgridComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ FlexgridComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(FlexgridComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
