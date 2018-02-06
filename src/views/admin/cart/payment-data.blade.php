@if( !empty($data->invoiceId) && isset($data->payment_data->status))
    @if($data->payment_data->status === 'succeeded')
        <div class="uk-form-row uk-alert uk-alert-success">
            <p>ID транзакции: {{ $data->payment_data->id }}</p>
            <p>Оплачено {{ \Carbon\Carbon::parse($data->payment_data->created_at)->format('d/M/Y h:s:i') }}<br/>
                Сумма: {{ $data->payment_data->amount->value }} {{ $data->payment_data->amount->currency }}</p>
            <form action="/ykassa/createRefund/{{ $data->payment_data->id }}" method="post">
                <button type="submit" class="uk-button uk-button-danger">Сделать возврат средств</button>
            </form>
        </div>
    @elseif($data->payment_data->status === 'pending')
        <div class="uk-form-row uk-alert uk-alert-warning">
            <p>ID транзакции: {{ $data->payment_data->id }}</p>
            <p>Ожидается оплата с {{ \Carbon\Carbon::parse($data->payment_data->created_at)->format('d/M/Y h:s:i') }}<br/>
                Сумма: {{ $data->payment_data->amount->value }} {{ $data->payment_data->amount->currency }}</p>
        </div>
    @elseif($data->payment_data->status === 'waiting_for_capture')
        <div class="uk-form-row uk-alert uk-alert-warning">
            <p>ID транзакции: {{ $data->payment_data->id }}</p>
            <p>Ожидается подтверждение магазина с {{ \Carbon\Carbon::parse($data->payment_data->created_at)->format('d/M/Y h:s:i') }}<br/>
                Сумма: {{ $data->payment_data->amount->value }} {{ $data->payment_data->amount->currency }}</p>
        </div>
    @elseif($data->payment_data->status === 'canceled')
        <div class="uk-form-row uk-alert uk-alert-danger">
            <p>ID транзакции: {{ $data->payment_data->id }}</p>
            <p>Оплата отменена {{ \Carbon\Carbon::parse($data->payment_data->created_at)->format('d/M/Y h:s:i') }}<br/>
                Сумма: {{ $data->payment_data->amount->value }} {{ $data->payment_data->amount->currency }}</p>
        </div>
    @endif
@endif