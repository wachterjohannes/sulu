// @flow
import {action, computed, observable} from 'mobx';
import queryString from 'query-string';

class PreviewConfigStore {
    @observable config;

    @action setConfig(config) {
        this.config = config;
    }

    @computed get routes(): ?Object {
        if (!this.config) {
            return null;
        }

        return this.config.routes;
    }

    @computed get debounceDelay(): ?Number {
        if (!this.config) {
            return null;
        }

        return this.config.debounceDelay;
    }

    generateRoute(name: string, options: Object) {
        return this.routes[name] + '?' + queryString.stringify(options);
    }
}

export default new PreviewConfigStore();
