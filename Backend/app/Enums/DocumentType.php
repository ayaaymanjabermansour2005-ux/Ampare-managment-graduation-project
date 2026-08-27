<?php

namespace App\Enums;

enum DocumentType: string
{
    case PaymentReceipt = 'payment_receipt';

    case GeneratorPhoto = 'generator_photo';
    case GeneratorLicense = 'generator_license';
    case GeneratorPurchaseInvoice = 'generator_purchase_invoice';

    case SubscriptionContract = 'subscription_contract';

    case TechnicianCertificate = 'technician_certificate';
    case TechnicianIdentity = 'technician_identity';

    case ComplaintImage = 'complaint_image';
    case ComplaintVideo = 'complaint_video';

    case MeterReadingPhoto = 'meter_reading_photo';
    case ChatAttachment = 'chat_attachment';
    case MessageAttachment = 'message_attachment';
    case ArticleImage = 'article_image';

    case OwnerApplicationDocument = 'owner_application_document';

    case OwnerIdentityDocument = 'owner_identity_document';
    case BusinessLicense = 'business_license';
    case GeneratorOwnershipContract = 'generator_ownership_contract';

    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PaymentReceipt => 'إيصال دفع',
            self::GeneratorPhoto => 'صورة المولد',
            self::GeneratorLicense => 'رخصة تشغيل المولد',
            self::GeneratorPurchaseInvoice => 'فاتورة شراء المولد',
            self::SubscriptionContract => 'عقد اشتراك',
            self::TechnicianCertificate => 'شهادة فني',
            self::TechnicianIdentity => 'هوية فني',
            self::ComplaintImage => 'صورة شكوى',
            self::ComplaintVideo => 'فيديو شكوى',
            self::MeterReadingPhoto => 'صورة قراءة عداد',
            self::ChatAttachment => 'مرفق محادثة ذكاء اصطناعي',
            self::MessageAttachment => 'مرفق رسالة',
            self::ArticleImage => 'صورة مقال',
            self::OwnerApplicationDocument => 'مستند طلب انضمام (عام)',
            self::OwnerIdentityDocument => 'الهوية الشخصية لمالك المولد',
            self::BusinessLicense => 'الرخصة التجارية',
            self::GeneratorOwnershipContract => 'عقد ملكية المولد',
            self::Other => 'أخرى',
        };
    }
}
