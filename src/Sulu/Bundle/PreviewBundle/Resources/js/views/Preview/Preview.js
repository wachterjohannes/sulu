// @flow
import React from 'react';
import ReactDOM from 'react-dom';
import {action, autorun, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import lodash from 'lodash';
import {sidebarStore} from 'sulu-admin-bundle/containers/Sidebar';
import {Icon} from 'sulu-admin-bundle/components';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import Requester from 'sulu-admin-bundle/services/Requester';
import PreviewStyles from './preview.scss';
import previewConfigStore from './stores/PreviewConfigStore';

type Props = {
    resourceStore: ResourceStore,
};

@observer
export default class Preview extends React.Component<Props> {
    @observable iframe;
    @observable token: string;
    @observable started: boolean = false;

    componentWillMount() {
        if (previewConfigStore.mode === 'auto') {
            this.startPreview();
        }
    }

    @action startPreview() {
        const {
            resourceStore,
            router: {
                attributes: {
                    locale,
                    webspace,
                },
            },
        } = this.props;

        Requester.get(previewConfigStore.generateRoute('start', {
            provider: resourceStore.resourceKey,
            webspace: webspace,
            locale: locale,
            id: resourceStore.id,
        })).then((response) => {
            this.setToken(response.token);
        });

        this.disposer = autorun(() => {
            if (resourceStore.loading || !this.iframe) {
                return;
            }

            this.updatePreview(toJS(resourceStore.data));
        });

        this.started = true;
    }

    updatePreview = lodash.debounce((data) => {
        const {
            router: {
                attributes: {
                    locale,
                    webspace,
                },
            },
        } = this.props;

        Requester.post(previewConfigStore.generateRoute('update', {
            webspace: webspace,
            locale: locale,
            token: this.token,
        }), {data: data}).then((response) => {
            const document = this.getPreviewDocument();
            document.open();
            document.write(response.content);
            document.close();
        });
    }, previewConfigStore.debounceDelay);

    componentWillUnmount() {
        if (!this.disposer) {
            return;
        }

        this.disposer();
        Requester.get(previewConfigStore.generateRoute('stop', {token: this.token}));
    }

    getPreviewDocument() {
        const iframe = ReactDOM.findDOMNode(this.iframe);
        const window = (iframe.contentWindow || iframe.contentDocument);

        return window.document ? window.document : window;
    }

    @action setToken(token) {
        this.token = token;
    }

    @action setIframe = (iframe) => {
        this.iframe = iframe;
    };

    handleToggleSidebarClick = () => {
        if (sidebarStore.size === 'medium') {
            return sidebarStore.setSize('large');
        }

        sidebarStore.setSize('medium');
    };

    handleRefreshClick = () => {
        const document = this.getPreviewDocument();

        document.location.reload();
    };

    render() {
        if (this.started === false) {
            return <button onClick={this.startPreview.bind(this)}>Start</button>;
        }

        if (!this.token) {
            return <h1>Loading ...</h1>;
        }

        const {
            router: {
                attributes: {
                    locale,
                    webspace,
                },
            },
        } = this.props;

        const url = previewConfigStore.generateRoute('render', {
            webspace: webspace,
            locale: locale,
            token: this.token,
        });

        return (
            <div className={PreviewStyles.container}>
                <iframe
                    ref={this.setIframe}
                    src={url}
                    className={PreviewStyles.iframe}
                />

                <button onClick={this.handleToggleSidebarClick}>
                    <Icon name={sidebarStore.size === 'medium' ? 'arrow-left' : 'arrow-right'}/>

                    Toggle sidebar
                </button>
                <button onClick={this.handleRefreshClick}>
                    <Icon name="refresh"/>

                    Refresh
                </button>
            </div>
        );
    }
}
