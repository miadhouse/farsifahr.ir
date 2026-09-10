<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionVideoResource\Pages;
use App\Models\QuestionVideo;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionVideoResource extends Resource
{
    protected static ?string $model = QuestionVideo::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'ویدیوهای آموزشی سوالات';
    protected static ?string $pluralModelLabel = 'ویدیوهای آموزشی سوالات';
    protected static ?string $modelLabel = 'ویدیو آموزشی';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('question_id')
                    ->relationship('question', 'id')
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "سوال {$record->id} (کد: {$record->number}) - " . substr(strip_tags($record->farsi_text ?? $record->text), 0, 80) . "...")
                    ->required()
                    ->label('سوال مرتبط'),
                Forms\Components\TextInput::make('title')
                    ->label('عنوان ویدیو')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('video_url')
                    ->label('فایل ویدیو آموزشی')
                    ->directory('question_videos')
                    ->disk('public')
                    ->required()
                    ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'])
                    ->maxSize(102400) // 100MB
                    ->hint('فایل ویدیویی MP4 یا مشابه آپلود کنید'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),
                Tables\Columns\TextColumn::make('question.number')
                    ->label('شماره سوال')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان ویدیو')
                    ->searchable(),
                Tables\Columns\TextColumn::make('video_url')
                    ->label('آدرس ویدیو')
                    ->limit(40),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageQuestionVideos::route('/'),
        ];
    }
}
