import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import Alert from 'flarum/components/Alert';
import punctuateSeries from 'flarum/helpers/punctuateSeries';
import username from 'flarum/helpers/username';

export default class ChangeReasonModal extends Modal {
    init() {
        this.item = this.props.item;

        this.reason = m.prop(this.item.reason());
    }

    className() {
        return 'Modal--medium';
    }

    title() {
        return app.translator.trans('fof-ban-ips.admin.modal.update_title');
    }

    content() {
        return (
            <div className="Modal-body">
                <div className="Form-group">
                    <label className="label">{app.translator.trans('fof-ban-ips.lib.modal.reason_label')}</label>
                    <input type="text" className="FormControl" bidi={this.reason} />
                </div>

                <div className="Form-group">
                    <Button className="Button Button--primary" type="submit" loading={this.loading} disabled={this.reason() === this.item.reason()}>
                        {app.translator.trans('fof-ban-ips.lib.modal.save_button')}
                    </Button>
                </div>
            </div>
        );
    }

    onsubmit(e) {
        e.preventDefault();

        if (!this.reason()) return;

        this.loading = true;

        this.item
            .save({
                reason: this.reason(),
            })
            .then(this.hide.bind(this))
            .catch(this.onerror.bind(this))
            .then(this.loaded.bind(this));
    }
}
