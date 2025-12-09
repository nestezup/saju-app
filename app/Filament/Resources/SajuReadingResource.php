<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SajuReadingResource\Pages;
use App\Models\SajuReading;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class SajuReadingResource extends Resource
{
    protected static ?string $model = SajuReading::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = '사주 기록';

    protected static ?string $modelLabel = '사주 기록';

    protected static ?string $pluralModelLabel = '사주 기록';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('기본 정보')
                    ->schema([
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('생년월일 (양력)')
                            ->required(),
                        Forms\Components\TextInput::make('birth_date_original')
                            ->label('입력 원본')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('birth_time')
                            ->label('태어난 시간')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_lunar')
                            ->label('음력 여부')
                            ->required(),
                        Forms\Components\Select::make('gender')
                            ->label('성별')
                            ->options([
                                'male' => '남성',
                                'female' => '여성',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('사주 결과')
                    ->schema([
                        Forms\Components\Textarea::make('saju_result')
                            ->label('사주 해석')
                            ->rows(10)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('daily_fortune')
                            ->label('오늘의 운세')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('메타데이터')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('사주 메타데이터')
                            ->columnSpanFull(),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->label('생년월일')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('birth_time')
                    ->label('시간')
                    ->default('-'),
                Tables\Columns\IconColumn::make('is_lunar')
                    ->label('음력')
                    ->boolean(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('성별')
                    ->formatStateUsing(fn (string $state): string => $state === 'male' ? '남성' : '여성'),
                Tables\Columns\TextColumn::make('metadata.year_gan')
                    ->label('년주')
                    ->formatStateUsing(function ($record) {
                        $meta = $record->metadata;
                        return ($meta['year_gan'] ?? '') . ($meta['year_ji'] ?? '');
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('생성일')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->label('성별')
                    ->options([
                        'male' => '남성',
                        'female' => '여성',
                    ]),
                Tables\Filters\TernaryFilter::make('is_lunar')
                    ->label('음력'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('기본 정보')
                    ->schema([
                        Infolists\Components\TextEntry::make('birth_date')
                            ->label('생년월일 (양력)')
                            ->date('Y년 m월 d일'),
                        Infolists\Components\TextEntry::make('birth_date_original')
                            ->label('입력 원본'),
                        Infolists\Components\TextEntry::make('birth_time')
                            ->label('태어난 시간')
                            ->default('-'),
                        Infolists\Components\IconEntry::make('is_lunar')
                            ->label('음력 여부')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('gender')
                            ->label('성별')
                            ->formatStateUsing(fn (string $state): string => $state === 'male' ? '남성' : '여성'),
                    ])->columns(3),

                Infolists\Components\Section::make('사주팔자')
                    ->schema([
                        Infolists\Components\TextEntry::make('metadata.year_gan')
                            ->label('년주')
                            ->formatStateUsing(function ($record) {
                                $meta = $record->metadata ?? [];
                                return ($meta['year_gan'] ?? '-') . ($meta['year_ji'] ?? '');
                            }),
                        Infolists\Components\TextEntry::make('metadata.month_gan')
                            ->label('월주')
                            ->formatStateUsing(function ($record) {
                                $meta = $record->metadata ?? [];
                                return ($meta['month_gan'] ?? '-') . ($meta['month_ji'] ?? '');
                            }),
                        Infolists\Components\TextEntry::make('metadata.day_gan')
                            ->label('일주')
                            ->formatStateUsing(function ($record) {
                                $meta = $record->metadata ?? [];
                                return ($meta['day_gan'] ?? '-') . ($meta['day_ji'] ?? '');
                            }),
                        Infolists\Components\TextEntry::make('metadata.hour_gan')
                            ->label('시주')
                            ->formatStateUsing(function ($record) {
                                $meta = $record->metadata ?? [];
                                return ($meta['hour_gan'] ?? '-') . ($meta['hour_ji'] ?? '');
                            }),
                    ])->columns(4),

                Infolists\Components\Section::make('사주 해석')
                    ->schema([
                        Infolists\Components\TextEntry::make('saju_result')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('오늘의 운세')
                    ->schema([
                        Infolists\Components\TextEntry::make('daily_fortune')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSajuReadings::route('/'),
            'create' => Pages\CreateSajuReading::route('/create'),
            'view' => Pages\ViewSajuReading::route('/{record}'),
            'edit' => Pages\EditSajuReading::route('/{record}/edit'),
        ];
    }
}
