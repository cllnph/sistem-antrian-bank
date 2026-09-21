<div class="bank-logo-container" style="display: inline-flex; align-items: center; gap: 8px;">
    <div class="bank-logo-card" style="display: inline-flex; align-items: center; background: rgba(255, 255, 255, 0.96); padding: {{ $padding ?? '4px 10px' }}; border-radius: 8px; box-shadow: {{ $shadow ?? '0 1px 3px rgba(0,0,0,0.12)' }}; border: 1px solid {{ $border ?? 'rgba(226, 232, 240, 0.8)' }};">
        <img src="{{ asset('images/bank-dp-taspen-logo.png') }}" 
             alt="Bank DP Taspen (Bank Perekonomian Rakyat)" 
             class="bank-dp-taspen-exact-logo select-none pointer-events-none" 
             style="height: {{ $height ?? ($size ?? '40px') }}; width: auto; object-fit: contain; display: block;" />
    </div>
    @if(!empty($showSlogan))
        <span class="brand-slogan-badge" style="background: #F3B313; color: #002B49; font-family: 'Inter', sans-serif; font-size: 11.5px; font-weight: 800; padding: 2px 10px; border-radius: 6px; letter-spacing: -0.2px; box-shadow: 0 2px 6px rgba(243, 179, 19, 0.35); white-space: nowrap;">
            #Mudah dan Nyaman
        </span>
    @endif
</div>

