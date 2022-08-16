<div class="row">
    <div class="col-md-12">
        Hello,
        <br/><br/>
        A Coupon Code was utilized for your organization {{ $orgName }}. The coupon details are:
        <br/><br/>
        Coupon Code: {{ $enterpriseCouponCode->coupon_code }}
        <br/>
        Allotted User(s): {{ $enterpriseCoupon->allotted_user_count }}
        <br/>
        Allotted Space (in GB): {{ $enterpriseCoupon->allotted_space_in_gb }} GB(s)     
        <br/>
        Utilized By: {{ $enterpriseCouponCode->utilizedByOrganizationAdmin->fullname }}     
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>