@extends("layouts.app")

@section('head')
    @parent
    {{Html::script("lib/jquery/jquery-3.2.1.min.js")}}
    {{Html::script("lib/jquery-ui/jquery-ui.min.js")}}
    {{Html::script("lib/tabulator/js/tabulator.min.js")}}
    {{Html::style("lib/tabulator/css/tabulator.min.css")}}
    {{Html::style("lib/tabulator/css/semantic-ui/tabulator_semantic-ui.min.css")}}
    {{Html::style("lib/jquery-ui/jquery-ui.min.css")}}
    {{Html::script("lib/toastr/toastr.min.js")}}
    {{Html::script("lib/sweetalert/sweetalert2.all.min.js")}}
    {{Html::style("lib/toastr/toastr.min.css")}}
    {{Html::script("lib/jquery-validation/jquery.validate.min.js")}}
    {{Html::script("js/common.js")}}
    @if (env("DIODE_IN", true))
      {{Html::script("js/diode-in.js")}}
    @else
      {{Html::script("js/diode-out.js")}}
    @endif
@endsection

@section("content")
    <div class="container">
        <div class="tab-v1">
        <!-- https://stackoverflow.com/questions/36559391/pass-array-to-laravel-view-and-use-that-array-as-vuejs-prop -->
            <uploaders 
              :items="{{ json_encode($uploaders) }}"
              :statuses="{{ json_encode($statuses) }}"
              :interval=3000
            ></uploaders>
        </div>
    </div>
@endsection