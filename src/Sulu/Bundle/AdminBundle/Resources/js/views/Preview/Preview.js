// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {sidebarStore} from '../../containers/Sidebar';
import Icon from '../../components/Icon/Icon';

type Props = {};

@observer
export default class Preview extends React.Component<Props> {
    handleToggleSidebarClick = () => {
        if (sidebarStore.size === 'medium') {
            return sidebarStore.setSize('large');
        }

        sidebarStore.setSize('medium');
    };

    render() {
        return (
            <div>
                <h1>HELLO world</h1>

                <button onClick={this.handleToggleSidebarClick}>
                    <Icon name={sidebarStore.size !== 'large' ? 'arrow-left' : 'arrow-right'} />

                    Toggle sidebar
                </button>
            </div>
        );
    }
}
