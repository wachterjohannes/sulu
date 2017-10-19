/* eslint-disable flowtype/require-valid-file-annotation */
import sidebarStore from '../../stores/SidebarStore';
import {DEFAULT_SIZE} from '../../types';

beforeEach(() => {
    sidebarStore.clearConfig();
});

test('Set sidebar config and let mobx react', () => {
    const config = {
        view: 'preview',
        props: {
            id: 1,
        },
        size: 'small',
    };

    sidebarStore.setConfig(config);

    expect(sidebarStore.enabled).toEqual(true);
    expect(sidebarStore.view).toEqual(config.view);
    expect(sidebarStore.props).toEqual(config.props);
    expect(sidebarStore.size).toEqual(config.size);
});

test('Default size of sidebar should be small', () => {
    sidebarStore.setConfig({
        view: 'preview',
    });

    expect(sidebarStore.view).toEqual('preview');
    expect(sidebarStore.size).toEqual(DEFAULT_SIZE);
});

test('Set sidebar size', () => {
    expect(sidebarStore.size).toEqual(DEFAULT_SIZE);
    sidebarStore.setSize('large');
    expect(sidebarStore.size).toEqual('large');
});

test('Clear sidebar config', () => {
    sidebarStore.setConfig({
        view: 'preview',
    });

    expect(sidebarStore.view).toEqual('preview');

    sidebarStore.clearConfig();
    expect(sidebarStore.view).toEqual(undefined);
});
