import { waitForAsync, ComponentFixture, TestBed } from '@angular/core/testing';

import { ConnectedMenuComponent } from './connected-menu.component';

describe('ConnectedMenuComponent', () => {
  let component: ConnectedMenuComponent;
  let fixture: ComponentFixture<ConnectedMenuComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ ConnectedMenuComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ConnectedMenuComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
