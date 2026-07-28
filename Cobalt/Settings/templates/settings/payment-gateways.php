<hgroup><h1>Payment Gateways</h1></hgroup>
<tab-nav>
    <nav>
        <a href="#stripe">Stripe</a>
        <a href="#paypal"><ion-icon name="logo-paypal"></ion-icon>PayPal</a>
    </nav>
    <div id="stripe">
        <form-request method="PUT" action="/api/v1/settings/payment-gateways/{{stripe._id}}">
            <ul class="list-panel">
                <li>
                    <label>Enable Stripe</label>
                    <input-switch name="enable" checked="{{stripe.checked}}"></input-switch>
                </li>
                <li>
                    <label>Stripe Credentials</label>
                    <input type="" name="secret" placeholder="Secret">
                    <input type="" name="token" placeholder="Token">
                </li>
            </ul>
            <input type="hidden" name="provider" value="stripe">
            <input type="hidden" name="type">
            <button type="submit">Save</button>
        </form-request>
    </div>
    <div id="paypal">
        <form-request method="PUT" action="/api/v1/settings/payment-gateways/{{paypal._id}}">
            <ul class="list-panel">
                <li>
                    <label>Enable PayPal</label>
                    <input-switch name="enable" checked="{{paypal.enable}}"></input-switch>
                </li>
                <li>
                    <label>Paypal Credentials</label>
                    <input type="" name="secret" placeholder="Secret">
                    <input type="" name="token" placeholder="Token">
                </li>
            </ul>
            <input type="hidden" name="provider" value="paypal">
            <input type="hidden" name="type">
            <button type="submit">Save</button>
        </form-request>
    </div>
</tab-nav>
