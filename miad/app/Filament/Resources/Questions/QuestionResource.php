<?php

namespace App\Filament\Resources\Questions;

use App\Filament\Resources\Questions\Pages\ManageQuestions;
use App\Models\Question;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

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
         Section::make(heading: 'متن قبل سوال')
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
                            ->label('انکلیسی')
                            ->rows(rows: 3)
                            ->columnSpanFull(),
                    ]),
Section::make('توضیح سوال')
                    ->schema([
                        RichEditor::make('info')
                            ->label('متن سوال')
                                ->resizableImages()
->columns(12)
                    ->columnSpanFull(),
                    ])->columns(12),
             Section::make('پاسخ‌های سوال')
                    ->schema([
                    Repeater::make('answers')
    ->relationship('answers')
    ->label('')
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
                                        'bold',
                                        'italic',
                                        'underline',
                                        'link',
                                        'attachFiles',
                                    ])
                                    ->columnSpan(12),
                            ])
                            ->columns(12)
                            ->itemLabel(fn (array $state): ?string => 
                                $state['text'] 
                                    ? (strlen($state['text']) > 50 
                                        ? substr($state['text'], 0, 50) . '...' 
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

                TextColumn::make('text')
                    ->label('متن سوال')
                    ->limit(60)
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->text),

              TextColumn::make('en_text')
                    ->label(label: 'متن انگلیسی')
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->en_text),
 TextColumn::make('farsi_text')
                    ->label(label: 'متن فارسی')
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
                    ->options([
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('سوال جدید')
                    ->icon(Heroicon::OutlinedPlus),
                    
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('number', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
    public static function getPages(): array
    {
        return [
            'index' => ManageQuestions::route('/'),
        ];
    }
}
