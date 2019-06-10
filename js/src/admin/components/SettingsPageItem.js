import Component from 'flarum/Component';
import Button from 'flarum/components/Button';
import username from 'flarum/helpers/username';

import UnbanIPModal from '../../common/components/UnbanIPModal';
import ChangeReasonModal from './ChangeReasonModal';

export default class SettingsPageItem extends Component {
    init() {
        this.item = this.props.bannedIP;
    }

    view() {
        return (
            <tr>
                <td>{this.item.id()}</td>
                <td>{username(this.item.creator())}</td>
                <td>{this.item.user() && username(this.item.user())}</td>
                <td>{this.item.address()}</td>
                <td>{this.item.reason()}</td>
                <td>{this.item.createdAt().toLocaleDateString()}</td>
                <td>
                    <div className="Button--group">
                        {Button.component({
                            className: 'Button Button--warning',
                            icon: 'fas fa-pencil-alt',
                            disabled: this.item.creator() !== app.session.user,
                            onclick: () => app.modal.show(new ChangeReasonModal({ item: this.item })),
                        })}
                        {Button.component({
                            className: 'Button Button--danger',
                            icon: 'fas fa-times',
                            onclick: () => app.modal.show(new UnbanIPModal({ address: this.item.address() })),
                        })}
                    </div>
                </td>
            </tr>
        );
    }
}