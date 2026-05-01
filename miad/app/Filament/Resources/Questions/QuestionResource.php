<?php

namespace App\Filament\Resources\Questions;

use App\Filament\Resources\Questions\Pages\ManageQuestions;
use App\Models\Question;
use App\Services\QuestionScraperService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // ─────────────────────────────────────────────
    // Form
    // ─────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('دسته‌های خاص (تگ‌ها)')
                    ->schema([
                        \Filament\Forms\Components\Select::make('tags')
                            ->multiple()
                            ->relationship('tags', 'name')
                            ->preload()
                            ->createOptionForm([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->label('نام دسته')
                                    ->unique('question_tags', 'name'),
                                \Filament\Forms\Components\ColorPicker::make('color')
                                    ->label('رنگ نمایش')
                            ])
                            ->label('تگ‌ها'),
                    ])->columns(1),

                Section::make('متن سوال')
                    ->schema([
                        Textarea::make('text')
                            ->label('آلمانی')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('en_text')
                            ->label('انگلیسی')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('farsi_text')
                            ->label('فارسی')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('متن قبل سوال')
                    ->schema([
                        Textarea::make('asw_pretext')
                            ->label('آلمانی')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('asw_farsi')
                            ->label('فارسی')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('asw_en')
                            ->label('انگلیسی')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('توضیح سوال')
                    ->schema([
                        RichEditor::make('info')
                            ->label('متن سوال')
                            ->columnSpanFull(),
                    ])->columns(12),

                Section::make('پاسخ‌های سوال')
                    ->schema([
                        Repeater::make('answers')
                            ->relationship('answers')
                            ->label('')
                            ->schema([
                                Textarea::make('text')
                                    ->label('متن آلمانی')
                                    ->rows(2)
                                    ->columnSpan(4),

                                Textarea::make('en_text')
                                    ->label('متن انگلیسی')
                                    ->rows(2)
                                    ->columnSpan(4),

                                Textarea::make('farsi_text')
                                    ->label('متن فارسی')
                                    ->rows(2)
                                    ->columnSpan(4),

                                RichEditor::make('info')
                                    ->label('توضیحات پاسخ')
                                    ->fileAttachmentsDisk('public')
                                    ->fileAttachmentsDirectory('answers/info')
                                    ->fileAttachmentsVisibility('public')
                                    ->toolbarButtons([
                                        'bold', 'italic', 'underline', 'link', 'attachFiles', 'blockquote', 'codeBlock', 'h2', 'h3', 'bulletList', 'orderedList', 'redo', 'undo'
                                    ])
                                    ->columnSpan(12),
                            ])
                            ->columns(12)
                            ->itemLabel(fn (array $state): ?string =>
                            !empty($state['text'])
                                ? (strlen($state['text']) > 50
                                ? mb_substr($state['text'], 0, 50) . '...'
                                : $state['text'])
                                : 'پاسخ جدید'
                            )
                            ->collapsed()
                            ->collapsible()
                            ->addActionLabel('افزودن پاسخ')
                            ->reorderable()
                            ->orderColumn('id')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columns(12)
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    // ─────────────────────────────────────────────
    // Table
    // ─────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('شماره')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                \Filament\Tables\Columns\TextColumn::make('tags.name')
                    ->label('تگ‌ها')
                    ->badge()
                    ->color(fn ($state, $record) => $record->tags->where('name', $state)->first()?->color ?? 'primary')
                    ->searchable(),

                TextColumn::make('text')
                    ->label('متن سوال')
                    ->limit(60)
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->text),

                TextColumn::make('en_text')
                    ->label('متن انگلیسی')
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->en_text),

                TextColumn::make('farsi_text')
                    ->label('متن فارسی')
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->farsi_text),

                TextColumn::make('info')
                    ->label('توضیح سوال')
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->info),
            ])
            ->filters([
                TernaryFilter::make('available')
                    ->label('فعال')
                    ->placeholder('همه')
                    ->trueLabel('فعال')
                    ->falseLabel('غیرفعال'),

                TernaryFilter::make('basic')
                    ->label('پایه')
                    ->placeholder('همه')
                    ->trueLabel('پایه')
                    ->falseLabel('غیر پایه'),

                TernaryFilter::make('basic_mofa')
                    ->label('پایه موفا')
                    ->placeholder('همه'),

                SelectFilter::make('points')
                    ->label('امتیاز')
                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'])
                    ->multiple(),
            ])
            ->actions([

                // ══════════════════════════════════════════════════
                //  دکمه واکشی — پیش‌نمایش محتوا، سپس ذخیره
                // ══════════════════════════════════════════════════
                Action::make('fetch')
                    ->label('واکشی')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->color('info')
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->modalHeading(fn (Question $record) => "واکشی اطلاعات: {$record->number}")
                    ->modalSubmitActionLabel('ذخیره اطلاعات')
                    ->modalCancelActionLabel('انصراف')

                    // هنگام باز شدن مودال: واکشی از سایت + پرکردن فرم پیش‌نمایش
                    ->mountUsing(function (Form $form, Question $record): void {
                        /** @var QuestionScraperService $scraper */
                        $scraper = app(QuestionScraperService::class);

                        $errorMsg       = null;
                        $questionInfo   = '';
                        $answersPayload = '[]';

                        try {
                            $data      = $scraper->scrape($record->number, (string) $record->text);
                            $dbAnswers = $record->answers()->orderBy('id')->get();

                            $germanQuestionInfo = $data['question_info'] ?? '';
                            $questionInfo = '';
                            
                            // فقط ترجمه فارسی برای توضیح سوال
                            if (!empty($germanQuestionInfo)) {
                                $faQuestionInfo = $scraper->translate($germanQuestionInfo);
                                if ($faQuestionInfo) {
                                    $questionInfo = "<div dir='rtl'>{$faQuestionInfo}</div>";
                                }
                            }

                            $matched = [];
                            foreach ($dbAnswers as $dbAnswer) {
                                $matchedInfo = null;
                                foreach ($data['answers'] as $scraped) {
                                    if ($dbAnswer->text && $scraper->textsMatch(
                                            (string) $dbAnswer->text,
                                            (string) $scraped['text']
                                        )) {
                                        $germanMatchedInfo = $scraped['info'];
                                        
                                        // فقط ترجمه فارسی برای توضیح پاسخ
                                        if (!empty($germanMatchedInfo)) {
                                            $faMatchedInfo = $scraper->translate($germanMatchedInfo);
                                            if ($faMatchedInfo) {
                                                $matchedInfo = "<div dir='rtl'>{$faMatchedInfo}</div>";
                                            }
                                        }
                                        break;
                                    }
                                }
                                $matched[] = [
                                    'id'   => $dbAnswer->id,
                                    'text' => mb_substr((string) $dbAnswer->text, 0, 100),
                                    'info' => $matchedInfo ?? '',
                                ];
                            }

                            $answersPayload = json_encode($matched, JSON_UNESCAPED_UNICODE);

                        } catch (\Throwable $e) {
                            Log::error('[QuestionScraper/fetch] ' . $e->getMessage(), [
                                'question' => $record->number,
                            ]);
                            $errorMsg = $e->getMessage();
                        }

                        $form->fill([
                            'fetch_error'         => $errorMsg,
                            'fetch_question_info' => $questionInfo,
                            'fetch_answers'       => $answersPayload,
                        ]);
                    })

                    // فرم پیش‌نمایش داخل مودال
                    ->form([

                        // پیام خطا (اگر واکشی ناموفق بود)
                        Placeholder::make('_error_box')
                            ->label('')
                            ->content(fn ($get): HtmlString => new HtmlString(
                                '<div class="rounded-lg bg-red-50 border border-red-300 '
                                . 'text-red-700 px-4 py-3 text-sm">'
                                . '⚠️ خطا در واکشی: ' . e($get('fetch_error'))
                                . '</div>'
                            ))
                            ->columnSpanFull()
                            ->visible(fn ($get): bool => ! empty($get('fetch_error'))),

                        // توضیح سوال
                        Placeholder::make('_question_info_box')
                            ->label('📄 توضیح سوال (از سایت)')
                            ->content(fn ($get): HtmlString => new HtmlString(
                                '<div class="rounded-lg bg-blue-50 border border-blue-200 '
                                . 'text-blue-900 px-4 py-3 text-sm leading-relaxed">'
                                . (
                                $get('fetch_question_info')
                                    ? $get('fetch_question_info')
                                    : '<span class="text-gray-400 italic">توضیحی برای سوال در سایت یافت نشد</span>'
                                )
                                . '</div>'
                            ))
                            ->columnSpanFull()
                            ->visible(fn ($get): bool => empty($get('fetch_error'))),

                        // توضیح پاسخ‌ها
                        Placeholder::make('_answers_box')
                            ->label('💬 توضیح پاسخ‌ها (از سایت)')
                            ->content(function ($get): HtmlString {
                                $answers = json_decode($get('fetch_answers') ?? '[]', true);

                                if (empty($answers)) {
                                    return new HtmlString(
                                        '<p class="text-gray-400 italic text-sm">پاسخی برای نمایش وجود ندارد</p>'
                                    );
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($answers as $i => $answer) {
                                    $num        = $i + 1;
                                    $answerText = e($answer['text']);
                                    $infoHtml   = $answer['info']
                                        ? $answer['info']
                                        : '<span class="text-gray-400 italic">بدون توضیح</span>';

                                    $html .= <<<HTML
                                        <div class="rounded-lg border border-gray-200 overflow-hidden text-sm">
                                            <div class="bg-gray-100 px-3 py-1.5 font-semibold text-gray-700">
                                                پاسخ {$num} — {$answerText}
                                            </div>
                                            <div class="px-3 py-2 text-gray-800 leading-relaxed">
                                                {$infoHtml}
                                            </div>
                                        </div>
                                    HTML;
                                }
                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull()
                            ->visible(fn ($get): bool => empty($get('fetch_error'))),

                        // داده‌های مخفی برای ذخیره‌سازی
                        Hidden::make('fetch_error'),
                        Hidden::make('fetch_question_info'),
                        Hidden::make('fetch_answers'),
                    ])

                    // ذخیره پس از تأیید کاربر
                    ->action(function (Question $record, array $data): void {
                        if (! empty($data['fetch_error'])) {
                            Notification::make()
                                ->title('ذخیره لغو شد — خطا در واکشی')
                                ->warning()
                                ->send();
                            return;
                        }

                        $savedInfo    = false;
                        $savedAnswers = 0;

                        if (! empty($data['fetch_question_info'])) {
                            $record->update([
                                'info' => $data['fetch_question_info'],
                            ]);
                            $savedInfo = true;
                        }

                        foreach (json_decode($data['fetch_answers'] ?? '[]', true) as $matched) {
                            if (! empty($matched['info'])) {
                                $record->answers()
                                    ->where('id', $matched['id'])
                                    ->update(['info' => $matched['info']]);
                                $savedAnswers++;
                            }
                        }

                        $lines = [
                            $savedInfo ? '✅ توضیح سوال ذخیره شد' : 'ℹ️ توضیحی برای سوال وجود نداشت',
                            "✅ توضیح {$savedAnswers} پاسخ ذخیره شد",
                        ];

                        Notification::make()
                            ->title("سوال {$record->number} به‌روزرسانی شد")
                            ->body(implode("\n", $lines))
                            ->success()
                            ->send();
                    }),

                // ══════════════════════════════════════════════════
                //  دکمه ربات — ذخیره مستقیم بدون پیش‌نمایش
                // ══════════════════════════════════════════════════
                Action::make('scrape')
                    ->label('ربات')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Question $record) => "جمع‌آوری خودکار: {$record->number}")
                    ->modalDescription('اطلاعات بلافاصله از سایت واکشی و ذخیره می‌شود.')
                    ->modalSubmitActionLabel('شروع')
                    ->action(function (Question $record): void {
                        /** @var QuestionScraperService $scraper */
                        $scraper = app(QuestionScraperService::class);

                        try {
                            $data         = $scraper->scrape($record->number, (string) $record->text);
                            $savedInfo    = false;
                            $savedAnswers = 0;

                            if (! empty($data['question_info'])) {
                                $faQuestionInfo = $scraper->translate($data['question_info']);
                                $finalInfo = $faQuestionInfo ? "<div dir='rtl'>{$faQuestionInfo}</div>" : $data['question_info'];
                                
                                $record->update([
                                    'info' => $finalInfo,
                                ]);
                                $savedInfo = true;
                            }

                            foreach ($record->answers()->get() as $dbAnswer) {
                                foreach ($data['answers'] as $scraped) {
                                    if ($dbAnswer->text && $scraper->textsMatch(
                                            (string) $dbAnswer->text,
                                            (string) $scraped['text']
                                        )) {
                                        if (! empty($scraped['info'])) {
                                            $faMatchedInfo = $scraper->translate($scraped['info']);
                                            $finalAnsInfo = $faMatchedInfo ? "<div dir='rtl'>{$faMatchedInfo}</div>" : $scraped['info'];

                                            $dbAnswer->update([
                                                'info' => $finalAnsInfo,
                                            ]);
                                            $savedAnswers++;
                                        }
                                        break;
                                    }
                                }
                            }

                            $lines = [
                                $savedInfo ? '✅ توضیح سوال ذخیره شد' : 'ℹ️ توضیحی برای سوال یافت نشد',
                                "✅ توضیح {$savedAnswers} پاسخ ذخیره شد",
                            ];

                            Notification::make()
                                ->title("سوال {$record->number} به‌روزرسانی شد")
                                ->body(implode("\n", $lines))
                                ->success()
                                ->send();

                        } catch (\Throwable $e) {
                            Log::error('[QuestionScraper] ' . $e->getMessage(), [
                                'question' => $record->number,
                            ]);

                            Notification::make()
                                ->title('خطا در جمع‌آوری اطلاعات')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('سوال جدید')
                    ->icon('heroicon-o-plus'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('number', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    // ─────────────────────────────────────────────
    // Pages
    // ─────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ManageQuestions::route('/'),
        ];
    }
}
