<?php $group = $colloque->options->groupBy('type'); ?>
@foreach($group as $type => $options)

    <!-- Options checkboxes -->
    @if($type == 'checkbox')
        <div class="well well-sm">
            @foreach($options as $option)
                <div class="form-group"><input type="checkbox" name="options[]" value="{{ $option->id }}" /> &nbsp;{{ $option->title }}</div>
            @endforeach
        </div>
    @endif

    <!-- Options radio -->
    @if($type == 'choix')
        @foreach($options as $option)
            <div class="form-group group-choix">
                <label class="control-label">{{ $option->title }}</label>
                <?php $option->load('groupe'); ?>
                @if(!$option->groupe->isEmpty())
                    @foreach($option->groupe as $groupe)
                        <div class="radio">
                            <label>
                                <input type="radio" required name="{{ $select }}[{{ $option->id }}]" value="{{ $groupe->id }}">
                                {{ $groupe->text }}
                            </label>
                        </div>
                    @endforeach
                @endif

            </div>
        @endforeach
    @endif

@endforeach