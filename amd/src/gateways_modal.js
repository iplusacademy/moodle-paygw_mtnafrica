// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This module is responsible for MTN Africa content in the gateways modal.
 *
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from './repository';
import Templates from 'core/templates';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';

/**
 * Creates and shows a modal that contains a placeholder.
 *
 * @returns {Promise<Modal>}
 */
const showModalWithPlaceholder = async() => {
    const modal = await ModalFactory.create({
        body: await Templates.render('paygw_mtnafrica/placeholder', {})
    });
    modal.show();
    return modal;
};

/**
 * Process the payment.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @param {string} description Description of the payment
 * @returns {Promise<string>}
 */
export const process = (component, paymentArea, itemId, description) => {
    return Promise.all([
        showModalWithPlaceholder(),
        Repository.getConfigForJs(component, paymentArea, itemId),
    ])
    .then(([modal, mtnConfig]) => {
        modal.setTitle(getString('pluginname', 'paygw_mtnafrica'));
        console.log(description);  // eslint-disable-line
        const phoneNumber = modal.getRoot().find('#mtn-phone');
        phoneNumber.append('<h4>' + mtnConfig.phone + '</h4>');
        const userCountry = modal.getRoot().find('#mtn-country');
        userCountry.append('<h4>' + mtnConfig.usercountry + '</h4>');
        const extraDiv = modal.getRoot().find('#mtn-extra');
        extraDiv.append('<h4>' + mtnConfig.cost + ' ' + mtnConfig.currency + '</h4>');
        const payForm = modal.getRoot().find('#mtn-form');
        payForm.append('<input type="hidden" name="userid" value="' + mtnConfig.userid + '" />');
        payForm.append('<input type="hidden" name="phone" value="' + mtnConfig.phone + '" />');
        payForm.append('<input type="hidden" name="country" value="' + mtnConfig.country + '" />');
        payForm.append('<input type="hidden" name="component" value="' + component + '" />');
        payForm.append('<input type="hidden" name="paymentarea" value="' + paymentArea + '" />');
        payForm.append('<input type="hidden" name="itemid" value="' + itemId + '" />');
        payForm.append('<input type="hidden" name="description" value="' + description + '" />');
        payForm.append('<input type="hidden" name="reference" value="' + mtnConfig.reference + '" />');
        modal.getRoot().on(ModalEvents.hidden, () => {
            // Destroy when hidden.
            console.log('Destroy modal');  // eslint-disable-line
            modal.destroy();
        });

        return Promise.all([modal, mtnConfig]);
    })
    .then(([modal, mtnConfig]) => {
        const cancelButton = modal.getRoot().find('#mtn-cancel');
        cancelButton.on('click', function() {
            modal.destroy();
        });
        const payButton = modal.getRoot().find('#mtn-pay');
        payButton.removeAttr('disabled');
        payButton.on('click', function(e) {
            e.preventDefault();
            modal.setBody(Templates.render('paygw_mtnafrica/busy', {}));

            return Promise.all([
                Repository.transactionStart(component, paymentArea, itemId, mtnConfig.reference, mtnConfig.phone, mtnConfig.country)
            ])
            .then(([mtnPay]) => {
                const cancelButton1 = modal.getRoot().find('#mtn-cancel');
                cancelButton1.on('click', function() {
                    modal.destroy();
                });
                if (mtnPay.code == 200) {
                    console.log('mtn Africa payment process started');  // eslint-disable-line
                    console.log('TransactionId: ' + mtnPay.xreferenceid);  // eslint-disable-line
                    console.log('Token: ' + mtnPay.token);  // eslint-disable-line
                    const outDiv = modal.getRoot().find('#mtn-out');
                    const spinnerDiv = modal.getRoot().find('#mtn-spinner');
                    outDiv.append('<h4>TransactionId: ' + mtnPay.xreferenceid + '</h4>');
                    var arrayints = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
                    var interval = 20000;
                    const b = '</div>';
                    const progressDiv = modal.getRoot().find('#mtn-progress_bar');
                    arrayints.forEach(function(el, index) {
                        setTimeout(function() {
                            progressDiv.attr('value', el * 10);
                            if (mtnPay.xreferenceid != '') {
                                modal.setFooter('Step ' + el + '/10');
                                Promise.all([
                                    Repository.transactionComplete(
                                        component, paymentArea, itemId, mtnPay.transactionid, mtnConfig.userid, mtnPay.token),
                                ])
                                .then(([mtnPing]) => {
                                    modal.setFooter(mtnPing.message);
                                    console.log(mtnPing.message);  // eslint-disable-line
                                    if (mtnPing.success) {
                                        if (mtnPing.message == 'SUCCESSFUL') {
                                            const a = '<br/><div class="p-3 mb-2 text-success font-weight-bold">';
                                            outDiv.append(a + mtnPing.message + b);
                                            const payButton1 = modal.getRoot().find('#mtn-pay');
                                            payButton1.removeAttr('disabled');
                                            payButton1.on('click', function() {
                                                modal.destroy();
                                            });
                                            spinnerDiv.attr('style', 'display: none;');
                                            cancelButton1.attr('style', 'display: none;');
                                            return;
                                        }
                                    } else {
                                        const a = '<br/><div class="p-3 mb-2 bg-danger text-white font-weight-bold">';
                                        outDiv.append(a + mtnPing.message + b);
                                        spinnerDiv.attr('style', 'display: none;');
                                        return;
                                    }
                                });
                            }
                            if (el == 10) {
                                modal.destroy();
                            }
                        }, index * interval);
                    });
                } else {
                    console.log('mtn Africa transaction FAILED');  // eslint-disable-line
                    modal.setFooter('FAILED');
                }
            }).catch(e => {
                // We want to use promise reject here - as that's what core payment stuff expects.
                console.log('mtn Africa payment rejected');  // eslint-disable-line
                return Promise.reject(e.message);
            });
        });
        return new Promise(() => null);
    }).catch(e => {
        return Promise.reject(e.message);
    });
};
