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

type Props = {
    resourceStore: ResourceStore,
};

@observer
export default class Preview extends React.Component<Props> {
    @observable iframe;
    @observable token: string;

    componentWillMount() {
        const {
            resourceStore,
            router: {
                attributes: {
                    locale,
                    webspace,
                },
            },
        } = this.props;

        // TODO where to get URL?
        Requester.get('/admin/preview/start?provider=' + resourceStore.resourceKey + '&webspace=' + webspace + '&locale=' + locale + '&id=' + resourceStore.id).then((response) => {
            this.setToken(response.token);
        });

        this.disposer = autorun(() => {
            if (resourceStore.loading || !this.iframe) {
                return;
            }

            this.updatePreview(toJS(resourceStore.data));
        });
    }

    // TODO get debounce wait from symfony-config
    updatePreview = lodash.debounce((data) => {
        // TODO where to get URL?
        Requester.post('/admin/preview/update?webspace=example&locale=en&token=' + this.token, {data: data}).then((response) => {
            const document = this.getPreviewDocument();
            document.open();
            document.write(response.content);
            document.close();
        });
    }, 250);

    componentWillUnmount() {
        this.disposer();

        // TODO where to get URL?
        Requester.get('/admin/preview/stop?token=' + this.token);
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
        if (!this.token) {
            return <h1>loading ...</h1>;
        }

        const {
            router: {
                attributes: {
                    locale,
                    webspace,
                },
            },
        } = this.props;

        // TODO where to get URL?
        const url = '/admin/preview/render?webspace=' + webspace + '&locale=' + locale + '&token=' + this.token;

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
