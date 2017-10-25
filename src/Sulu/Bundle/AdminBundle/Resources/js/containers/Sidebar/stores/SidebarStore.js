// @flow
import {action, computed, observable} from 'mobx';
import type {SidebarConfig, Sizes} from '../types';
import {DEFAULT_SIZE, SIZES} from '../types';

class SidebarStore {
    @observable view: ?string;
    @observable props: Object;
    sizes: Array<string>;

    @observable size: ?string;

    constructor() {
        this.clearConfig();
    }

    @action setConfig(config: SidebarConfig) {
        this.view = config.view;
        this.props = config.props || {};
        this.sizes = config.sizes || SIZES;

        let defaultSize = config.defaultSize || DEFAULT_SIZE;
        if (!this.size || -1 === this.sizes.indexOf(this.size)) {
            this.size = defaultSize;
        }
    }

    @action clearConfig() {
        this.setConfig({});
        this.size = null;
    }

    @computed get enabled(): boolean {
        return !!this.view;
    }

    @action setSize(size: Sizes) {
        if (-1 === this.sizes.indexOf(size)) {
            throw new Error(
                'Size "' + size + '" is not supported by view. Supported: ["' + this.sizes.join('", "') + '"]'
            );
        }

        this.size = size;
    }
}

export default new SidebarStore();
