@extends('cms::admin_template')
@section('content')

    <div>

        @if(CMS::getCurrentMethod() != 'getProfile' && $button_cancel)
            @if(g('return_url'))
                <p><a title='Return' href='{{g("return_url")}}'><i class='fa fa-chevron-circle-left '></i>
                        &nbsp; {{cbLang("form_back_to_list",['module'=>CMS::getCurrentModule()->name])}}</a></p>
            @else
                <p><a title='Main Module' href='{{CMS::mainpath()}}'><i class='fa fa-chevron-circle-left '></i>
                        &nbsp; {{cbLang("form_back_to_list",['module'=>CMS::getCurrentModule()->name])}}</a></p>
            @endif
        @endif

        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class='{{CMS::getCurrentModule()->icon}}'></i> {!! $page_title !!}</strong>
            </div>

            <div class="panel-body" style="padding:20px 0px 0px 0px">
                <?php
                $action = (@$row) ? CMS::mainpath("edit-save/$row->id") : CMS::mainpath("add-save");
                $return_url = ($return_url) ?: g('return_url');
                ?>
                <form class='form-horizontal' method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type='hidden' name='return_url' value='{{ @$return_url }}'/>
                    <input type='hidden' name='ref_mainpath' value='{{ CMS::mainpath() }}'/>
                    <input type='hidden' name='ref_parameter' value='{{urldecode(http_build_query(@$_GET))}}'/>
                    @if($hide_form)
                        <input type="hidden" name="hide_form" value='{!! serialize($hide_form) !!}'>
                    @endif
                    <div class="box-body" id="parent-form-area">

                        @if($command == 'detail')
                            @include("cms::default.form_detail")
                        @else
                            @include("cms::default.form_body")
                        @endif
                    </div><!-- /.box-body -->

                    <div class="box-footer" style="background: #F5F5F5">

                        <div class="_form-group">
                            <div class="col-sm-12">
                                @if($button_cancel && CMS::getCurrentMethod() != 'getDetail')
                                    @if(g('return_url'))
                                        <a href='{{g("return_url")}}' class='btn btn-default'><i
                                                    class='fa fa-chevron-circle-left'></i> {{cbLang("button_back")}}</a>
                                    @else
                                        <a href='{{CMS::mainpath("?".http_build_query(@$_GET)) }}' class='btn btn-default'><i
                                                    class='fa fa-chevron-circle-left'></i> {{cbLang("button_back")}}</a>
                                    @endif
                                @endif
                                @if(CMS::isCreate() || CMS::isUpdate())

                                    @if(CMS::isCreate() && $button_addmore==TRUE && $command == 'add')
                                        <input type="submit" name="submit" value='{{cbLang("button_save_more")}}' class='btn btn-success'>
                                    @endif

                                    @if($button_save && $command != 'detail')
                                        <input type="submit" name="submit" value='{{cbLang("button_save")}}' class='btn btn-success'>
                                    @endif

                                @endif
                            </div>
                        </div>


                    </div><!-- /.box-footer-->

                </form>

            </div>
        </div>
    </div><!--END AUTO MARGIN-->

@endsection
