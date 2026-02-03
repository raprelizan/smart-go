<section>
    <h2>{{ $settings['title'] ?? ucfirst(str_replace('_', ' ', 'cta_order_form')) }}</h2>
    @if (!empty($settings['subtitle']))
        <p>{{ $settings['subtitle'] }}</p>
    @endif
    @foreach ($blocks as $block)
        <div>
            <strong>{{ $block['type'] ?? 'block' }}</strong>
            <pre>{{ json_encode($block['settings'] ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endforeach
</section>
