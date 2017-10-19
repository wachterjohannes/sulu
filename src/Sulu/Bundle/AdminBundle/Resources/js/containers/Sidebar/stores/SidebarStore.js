// @flow
import {action, computed, observable} from 'mobx';
import type {SidebarConfig, Sizes} from '../types';
import {DEFAULT_SIZE} from '../types';

class SidebarStore {
    @observable config: SidebarConfig;
    @observable size: string;

    constructor() {
        this.clearConfig();
    }

    @action setConfig(config: SidebarConfig) {
        this.config = config;
        this.size = config.size || DEFAULT_SIZE;
    }

    @action clearConfig() {
        this.setConfig({});
    }

    @computed get enabled(): boolean {
        return !!this.config.view;
    }

    @computed get view(): ?string {
        return this.config.view;
    }

    @computed get props(): ?Object {
        return this.config.props;
    }

    @action setSize(size: Sizes) {
        this.size = size;
    }
}

export default new SidebarStore();
