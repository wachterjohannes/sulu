// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {sidebarStore} from '../../containers/Sidebar';
import Icon from '../../components/Icon/Icon';

type Props = {};

@observer
export default class Preview extends React.PureComponent<Props> {
    handleToggleSidebarClick = () => {
        if (sidebarStore.size === 'small') {
            return sidebarStore.setSize('middle');
        } else if (sidebarStore.size === 'middle') {
            return sidebarStore.setSize('large');
        }

        sidebarStore.setSize('small');
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
