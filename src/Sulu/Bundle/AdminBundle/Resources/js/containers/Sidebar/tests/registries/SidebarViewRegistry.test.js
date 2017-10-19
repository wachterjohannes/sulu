/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import sidebarViewRegistry from '../../registries/SidebarViewRegistry';

const component = (props) => (<h1>{props.title}</h1>);

beforeEach(() => {
    sidebarViewRegistry.clear();
});

test('Find out if the sidebar-view-registry has a named view', () => {
    expect(sidebarViewRegistry.has('test')).toEqual(false);

    sidebarViewRegistry.add('test', component);
    expect(sidebarViewRegistry.has('test')).toEqual(true);
});

test('Get named view from sidebar-view-registry', () => {
    sidebarViewRegistry.add('test', component);
    expect(sidebarViewRegistry.get('test')).toEqual(component);
});
