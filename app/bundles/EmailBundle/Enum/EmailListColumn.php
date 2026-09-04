<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Enum;

enum EmailListColumn: string
{
    case Name          = 'name';
    case Subject       = 'subject';
    case Description   = 'description';
    case EmailType     = 'emailType';
    case Language      = 'language';
    case Category      = 'category';
    case Template      = 'template';
    case IsPublished   = 'isPublished';
    case Stats         = 'stats';
    case PublishUp     = 'publishUp';
    case PublishDown   = 'publishDown';
    case DateAdded     = 'dateAdded';
    case DateModified  = 'dateModified';
    case CreatedByUser = 'createdByUser';
    case Id            = 'id';

    public function labelKey(): string
    {
        return match ($this) {
            self::Name          => 'mautic.core.name',
            self::Subject       => 'mautic.email.subject',
            self::Description   => 'mautic.core.description',
            self::EmailType     => 'mautic.email.column.type',
            self::Language      => 'mautic.core.language',
            self::Category      => 'mautic.core.category',
            self::Template      => 'mautic.core.form.theme',
            self::IsPublished   => 'mautic.email.column.is_published',
            self::Stats         => 'mautic.core.stats',
            self::PublishUp     => 'mautic.core.form.activate_at',
            self::PublishDown   => 'mautic.core.form.publishdown',
            self::DateAdded     => 'mautic.lead.import.label.dateAdded',
            self::DateModified  => 'mautic.lead.import.label.dateModified',
            self::CreatedByUser => 'mautic.core.createdby',
            self::Id            => 'mautic.core.id',
        };
    }

    public function isDefault(): bool
    {
        return match ($this) {
            self::Name,
            self::Category,
            self::Template,
            self::Stats,
            self::DateAdded,
            self::DateModified,
            self::CreatedByUser,
            self::Id => true,
            default  => false,
        };
    }

    /**
     * @return array{class: string, orderBy?: string, defaultDir?: string, default?: bool}
     */
    public function headerMeta(): array
    {
        return match ($this) {
            self::Name => [
                'orderBy' => 'e.name',
                'class'   => 'col-email-name',
            ],
            self::Subject => [
                'orderBy' => 'e.subject',
                'class'   => 'visible-md visible-lg col-email-subject',
            ],
            self::Description => [
                'orderBy' => 'e.description',
                'class'   => 'visible-lg col-email-description',
            ],
            self::EmailType => [
                'orderBy' => 'e.emailType',
                'class'   => 'visible-md visible-lg col-email-type',
            ],
            self::Language => [
                'orderBy' => 'e.language',
                'class'   => 'visible-md visible-lg col-email-language',
            ],
            self::Category => [
                'orderBy' => 'c.title',
                'class'   => 'visible-md visible-lg col-email-category',
            ],
            self::Template => [
                'orderBy' => 'e.template',
                'class'   => 'visible-md visible-lg col-email-template',
            ],
            self::IsPublished => [
                'orderBy' => 'e.isPublished',
                'class'   => 'visible-md visible-lg col-email-published',
            ],
            self::Stats => [
                'class' => 'visible-sm visible-md visible-lg col-email-stats',
            ],
            self::PublishUp => [
                'orderBy' => 'e.publishUp',
                'class'   => 'visible-lg col-email-publishUp',
            ],
            self::PublishDown => [
                'orderBy' => 'e.publishDown',
                'class'   => 'visible-lg col-email-publishDown',
            ],
            self::DateAdded => [
                'orderBy' => 'e.dateAdded',
                'class'   => 'visible-lg col-email-dateAdded',
            ],
            self::DateModified => [
                'orderBy'    => 'e.dateModified',
                'defaultDir' => 'DESC',
                'default'    => true,
                'class'      => 'visible-lg col-email-dateModified',
            ],
            self::CreatedByUser => [
                'orderBy' => 'e.createdByUser',
                'class'   => 'visible-lg col-email-createdByUser',
            ],
            self::Id => [
                'orderBy' => 'e.id',
                'class'   => 'visible-md visible-lg col-email-id',
            ],
        };
    }

    /**
     * @return list<string>
     */
    public static function defaultValues(): array
    {
        return array_map(
            static fn (self $column): string => $column->value,
            array_values(array_filter(self::cases(), static fn (self $column): bool => $column->isDefault()))
        );
    }
}
