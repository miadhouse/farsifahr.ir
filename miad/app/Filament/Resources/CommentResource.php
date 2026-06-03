<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'نظرات وبلاگ';
    protected static ?string $pluralModelLabel = 'نظرات';
    protected static ?string $modelLabel = 'نظر';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        TextInput::make('author_name')
                            ->label('نام نویسنده')
                            ->required(),
                        TextInput::make('author_email')
                            ->label('ایمیل')
                            ->required()
                            ->email(),
                        Select::make('post_id')
                            ->relationship('post', 'title')
                            ->label('مطلب')
                            ->required(),
                        Select::make('parent_id')
                            ->relationship('parent', 'author_name')
                            ->label('پاسخ به')
                            ->placeholder('نظر اصلی'),
                        Select::make('status')
                            ->label('وضعیت')
                            ->options([
                                'pending' => 'در انتظار تایید',
                                'approved' => 'تایید شده',
                                'rejected' => 'رد شده',
                            ])
                            ->required(),
                        Textarea::make('content')
                            ->label('متن نظر')
                            ->columnSpanFull()
                            ->required(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('post.title')
                    ->label('مطلب')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('author_name')
                    ->label('نویسنده')
                    ->searchable(),
                TextColumn::make('parent.author_name')
                    ->label('در پاسخ به')
                    ->placeholder('نظر اصلی')
                    ->toggleable(),
                TextColumn::make('content')
                    ->label('متن نظر')
                    ->limit(50),
                SelectColumn::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار تایید',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                    ]),
                TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                    ]),
                Tables\Filters\TernaryFilter::make('is_reply')
                    ->label('نوع')
                    ->placeholder('همه')
                    ->trueLabel('پاسخ‌ها')
                    ->falseLabel('نظرات اصلی')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('parent_id'),
                        false: fn ($query) => $query->whereNull('parent_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reply')
                    ->label('پاسخ ادمین')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('primary')
                    ->form([
                        Textarea::make('reply_content')
                            ->label('متن پاسخ ادمین')
                            ->required(),
                    ])
                    ->action(function (Comment $record, array $data): void {
                        Comment::create([
                            'post_id' => $record->post_id,
                            'parent_id' => $record->id,
                            'author_name' => 'مدیر سایت',
                            'author_email' => Auth::user()->email,
                            'content' => $data['reply_content'],
                            'status' => 'approved',
                        ]);
                        
                        // Auto-approve the original comment if it was pending
                        if ($record->status === 'pending') {
                            $record->update(['status' => 'approved']);
                        }
                    }),
                Tables\Actions\Action::make('approve')
                    ->label('تایید')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(fn (Comment $record) => $record->update(['status' => 'approved']))
                    ->visible(fn (Comment $record) => $record->status !== 'approved'),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
