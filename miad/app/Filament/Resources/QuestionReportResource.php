<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionReportResource\Pages;
use App\Models\QuestionReport;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class QuestionReportResource extends Resource
{
    protected static ?string $model = QuestionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'گزارشات سوالات';
    protected static ?string $pluralModelLabel = 'گزارشات';
    protected static ?string $modelLabel = 'گزارش';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات گزارش')
                    ->schema([
                        Forms\Components\Placeholder::make('user_name')
                            ->label('کاربر')
                            ->content(fn ($record) => "ID: {$record->user_id} | " . ($record->user?->name ?? 'نامشخص')),
                        
                        Forms\Components\Placeholder::make('question_info')
                            ->label('شناسه سوال')
                            ->content(fn ($record) => $record->question_id),

                        Textarea::make('message')
                            ->label('پیام کاربر')
                            ->readOnly(),

                        Select::make('status')
                            ->label('وضعیت')
                            ->options([
                                'pending' => 'در انتظار',
                                'approved' => 'تایید شده (هدیه داده شد)',
                                'rejected' => 'رد شده',
                            ])
                            ->required(),
                        
                        Textarea::make('rejection_reason')
                            ->label('دلیل رد گزارش')
                            ->placeholder('در صورت رد گزارش، علت را اینجا بنویسید...')
                            ->visible(fn ($get) => $get('status') === 'rejected'),
                    ])->columns(2),

                Forms\Components\Section::make('جزئیات سوال گزارش شده')
                    ->description('محتوای سوال در زمان بررسی')
                    ->schema([
                        Forms\Components\Placeholder::make('q_text')
                            ->label('متن آلمانی')
                            ->content(fn ($record) => $record->question?->text),
                        
                        Forms\Components\Placeholder::make('q_farsi')
                            ->label('ترجمه فارسی')
                            ->content(fn ($record) => $record->question?->farsi_text),

                        Forms\Components\Placeholder::make('q_info')
                            ->label('توضیحات سوال')
                            ->content(fn ($record) => strip_tags($record->question?->info)),

                        Forms\Components\Placeholder::make('answers')
                            ->label('گزینه‌ها و توضیحات آن‌ها')
                            ->content(function ($record) {
                                $output = "";
                                if ($record->question && $record->question->answers) {
                                    foreach ($record->question->answers as $ans) {
                                        $output .= "🔹 " . $ans->text . " (" . ($ans->farsi_text ?? 'فاقد ترجمه') . ")\n";
                                        if ($ans->info) {
                                            $output .= "   💡 توضیح: " . strip_tags($ans->info) . "\n\n";
                                        }
                                    }
                                }
                                return nl2br($output);
                            })->extraAttributes(['style' => 'direction: rtl; text-align: right;']),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user_id')->label('کاربر')->sortable(),
                TextColumn::make('question_id')->label('سوال')->sortable(),
                TextColumn::make('message')->label('پیام')->limit(50)->tooltip(fn($record) => $record->message),
                BadgeColumn::make('status')
                    ->label('وضعیت')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'در انتظار',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                        default => $state,
                    }),
                TextColumn::make('created_at')->label('تاریخ ثبت')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('تایید و اهدای هدیه')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'pending')
                    ->action(function (QuestionReport $record) {
                        $rewardDays = (int) env('REPORT_REWARD_DAYS', 10);
                        
                        // Logic to add subscription days
                        // check if user has active subscription
                        $sub = Subscription::where('user_id', $record->user_id)
                            ->where('status', 'active')
                            ->orderBy('expires_at', 'desc')
                            ->first();

                        if ($sub) {
                            $sub->expires_at = Carbon::parse($sub->expires_at)->addDays($rewardDays);
                            $sub->save();
                        } else {
                            Subscription::create([
                                'user_id' => $record->user_id,
                                'plan_id' => 1, // Assume plan 1 is gift
                                'status' => 'active',
                                'expires_at' => now()->addDays($rewardDays),
                                'duration_days' => $rewardDays,
                                'amount_paid' => 0,
                                'payment_method' => 'gift',
                            ]);
                        }

                        $record->status = 'approved';
                        $record->save();

                        Notification::make()
                            ->title('گزارش تایید شد و هدیه به کاربر تعلق گرفت')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رد گزارش')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('دلیل رد گزارش')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'pending')
                    ->action(function (QuestionReport $record, array $data) {
                        $record->status = 'rejected';
                        $record->rejection_reason = $data['rejection_reason'];
                        $record->save();

                        Notification::make()
                            ->title('گزارش رد شد')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestionReports::route('/'),
            'create' => Pages\CreateQuestionReport::route('/create'),
            'edit' => Pages\EditQuestionReport::route('/{record}/edit'),
        ];
    }
}
