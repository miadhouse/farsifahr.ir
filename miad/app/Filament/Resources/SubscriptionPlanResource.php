<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'اشتراک‌ها';
    protected static ?string $modelLabel = 'اشتراک';
    protected static ?string $pluralModelLabel = 'اشتراک‌ها';
    protected static ?string $navigationGroup = 'مدیریت مالی';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('اطلاعات پایه')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('نام پلن')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('slug')
                                ->label('نامک (Slug)')
                                ->required()
                                ->maxLength(50),
                            Forms\Components\TextInput::make('sort_order')
                                ->label('ترتیب نمایش')
                                ->numeric()
                                ->default(0),
                            Forms\Components\TextInput::make('question_limit')
                                ->label('محدودیت سوال')
                                ->numeric()
                                ->helperText('خالی بگذارید به معنای نامحدود است.')
                                ->default(null),
                            Forms\Components\Toggle::make('is_active')
                                ->label('فعال/غیرفعال')
                                ->default(true)
                                ->columnSpanFull(),
                        ]),
                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('features')
                            ->label('ویژگی‌های پلن')
                            ->placeholder('ویژگی را وارد کرده و اینتر بزنید')
                            ->helperText('ویژگی‌هایی مانند "دسترسی نامحدود" را وارد کنید.')
                            ->columnSpanFull(),
                    ]),

                Section::make('قیمت‌گذاری و مدت زمان (یورو)')
                    ->schema([
                        Forms\Components\Repeater::make('durations')
                            ->label('لیست قیمت‌ها و زمان‌ها')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('عنوان (مثلا: ۱ ماه)')
                                    ->required(),
                                Forms\Components\TextInput::make('days')
                                    ->label('تعداد روز')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('price')
                                    ->label('قیمت (یورو)')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),

                        Grid::make(3)->schema([
                            Forms\Components\TextInput::make('price_2_weeks')
                                ->label('قیمت ۲ هفته (یورو) - قدیمی')
                                ->numeric()
                                ->disabled(),
                            Forms\Components\TextInput::make('price_1_month')
                                ->label('قیمت ۱ ماه (یورو) - قدیمی')
                                ->numeric()
                                ->disabled(),
                            Forms\Components\TextInput::make('price_3_months')
                                ->label('قیمت ۳ ماه (یورو) - قدیمی')
                                ->numeric()
                                ->disabled(),
                            Forms\Components\TextInput::make('price_6_months')
                                ->label('قیمت ۶ ماه (یورو) - قدیمی')
                                ->numeric()
                                ->disabled(),
                            Forms\Components\TextInput::make('price_1_year')
                                ->label('قیمت ۱ سال (یورو) - قدیمی')
                                ->numeric()
                                ->disabled(),
                        ])->hidden(fn (?SubscriptionPlan $record) => $record && !empty($record->durations)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام پلن')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('نامک')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_1_month')
                    ->label('قیمت ۱ ماه (یورو)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('ترتیب')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
